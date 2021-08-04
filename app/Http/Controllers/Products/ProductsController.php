<?php

namespace App\Http\Controllers\Products;

use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Models\Order\Item;
use App\Models\Product\Category;
use App\Models\Product\Product;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request, CategoryService $categoryService)
    {
        $builder = Product::query()->where('on_sale', true);

        // 是否有关键字
        if ($search = $request->input('search', '')) {
            $like = '%' . $search . '%';

            $builder->where(function ($query) use ($like) {
                $query->where('title', 'like', $like)
                    ->orWhere('description', 'like', $like)
                    ->orWhereHas('skus', function ($query) use ($like) {
                        $query->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like);
                    });
            });
        }

        // 商品排列顺序
        if ($order = $request->input('order', '')) {
            // 判断是否以 asc 或者 desc 结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    $builder->orderBy($m[1], $m[2]);
                }
            }
        }

        if ($request->input('product_category_id') && $category = Category::find($request->input('product_category_id'))) {
            if ($category->is_directory || $category->hasChildren()) {
                $builder->whereHas('category', function ($query) use ($category) {
                    $query->where('path', 'like', $category->path . $category->id . '-%');
                });
            } else {
                $builder->where('product_category_id', $category->id);
            }
        }

        return view('products.index', [
            'products' => $builder->paginate(16),
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
