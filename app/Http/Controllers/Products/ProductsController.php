<?php

namespace App\Http\Controllers\Products;

use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Models\Order\Item;
use App\Models\Product\Category;
use App\Models\Product\Product;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    public function index(Request $request, CategoryService $categoryService)
    {
        $page = $request->input('page', 1);
        $perPage = 16;

        $params = [
            'index' => 'products',
            'body' => [
                'from' => ($page - 1) * $perPage,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'filter' => [
                            [
                                'term' => [
                                    'on_sale' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // 商品排列顺序
        if ($order = $request->input('order', '')) {
            // 判断是否以 asc 或者 desc 结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $params['body']['sort'] = [
                        $m[1] => $m[2],
                    ];
                }
            }
        }

        // 商品分类
        if ($request->input('product_category_id') && $category = Category::find($request->input('product_category_id'))) {
            if ($category->is_directory || $category->hasChildren()) {
                $params['body']['query']['bool']['filter'][] = [
                    'prefix' => [
                        'category_path' => $category->path . $category->id . '-',
                    ],
                ];
            } else {
                $params['body']['query']['bool']['filter'][] = [
                    'term' => [
                        'category_id' => $category->id,
                    ],
                ];
            }
        }

        // 关键词
        if ($search = $request->input('search', '')) {
            $keywords = array_filter(explode(' ', $search));

            $params['body']['query']['bool']['must'] =[];

            foreach ($keywords as $keyword) {
                $params['body']['query']['bool']['must'][] = [
                    'multi_match' => [
                        'query' => $keyword,
                        'fields' => [
                            'title^3',
                            'long_title^2',
                            'category^2',
                            'description',
                            'skus_title',
                            'skus_description',
                            'properties_value',
                        ],
                    ],
                ];
            }
        }


        $result = app('es')->search($params);

        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();

        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $productIds)))
            ->get();

        $pager = new LengthAwarePaginator($products, $result['hits']['total']['value'], $perPage, $page, [
            'path' => route('products.index', false),
        ]);

        return view('products.index', [
            'products' => $pager,
            'filters' => [
                'order' => $order,
                'search' => $search,
            ],
            'category' => $category ?? null,
        ]);
    }

    public function show(Product $product, Request $request)
    {
        if (!$product->on_sale) {
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;
        if($user = $request->user()) {
            $favored = boolval($user->favoriteProducts()->find($product->id));
        }

        $reviews = Item::query()
            ->with(['order.user', 'productSku'])
            ->where('product_Id', $product->id)
            ->whereNotNull('reviewed_at')
            ->orderBy('reviewed_at', 'desc')
            ->limit(10)
            ->get();

        return view('products.show', [
            'product' => $product,
            'favored' => $favored,
            'reviews' => $reviews,
        ]);
    }

    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites', [
            'products' => $products,
        ]);
    }

    public function favor(Product $product, Request $request)
    {
        $user = $request->user();

        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product, Request $request)
    { 
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }
}
