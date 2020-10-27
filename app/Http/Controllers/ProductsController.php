<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request, CategoryService $categoryService)
    {
        //创建查询构造器
        $builder = Product::query()->where('status',true);

        //search 参数用来模糊搜索商品
        if ($search = $request->input('search','')) {
            $like = '%'.$search.'%';

            $builder->where(function ($query) use ($like) {
                $query->where('title','like',$like)
                    ->orWhere('description','like','$like')
                    ->orWhereHas('skus',function ($query) use ($like) {
                        $query->where('title','like',$like)
                            ->orWhere('description','like',$like);
                    });
            });
        }

        // 如果有传入 category_id 字段，并且在数据库中有对应的类目
        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            // 如果传入的分类是父类
            if ($category->is_directory) {
                // 筛选出该父类下的所有子分类的商品
                $builder->whereHas('category', function ($query) use ($category) {
                    $query->where('path','like',$category->path.$category->id.'-%');
                });
            } else {
                // 如果不是父类
                $builder->where('category_id', $category->id);
            }
        }

        // order 参数控制商品的排序规则
        if ($order = $request->input('order','')) {
            if (preg_match('/^(.+)_(asc|desc)$/',$order,$m)) {
                if (in_array($m[1],['price','sold_count','rating'])) {
                    $builder->orderBy($m[1],$m[2]);
                }
            }
        }

        $products = $builder->paginate(16);

        return view('products.index',[
            'products' => $products,
            'filters' =>[
                'search' => $search,
                'order' => $order,
            ],
            'category' => $category ?? null,
            'categoryTree' => $categoryService->getCategoryTree(),
        ]);
    }

    public function show(Product $product,Request $request)
    {
        //判断商品是否上架
        if (!$product->status) {
            throw new InvalidRequestException('商品未上架');
        }

        $favored = false;

        if ($user = $request->user()) {
            $favored = boolval($user->favoriteProducts()->find($product));
        }

        $reviews = OrderItem::query()
            ->with(['order.user','productSku'])
            ->where('product_id',$product->id)
            ->whereNotNull('reviewed_at')
            ->orderBy('reviewed_at','desc')
            ->limit(10)
            ->get();

        return view('products.show',[
            'product' => $product,
            'favored' => $favored,
            'reviews' => $reviews,
        ]);
    }

    public function favor(Product $product,Request $request)
    {
        $user = $request->user();
        if ($user->favoriteProducts()->find($product->id)) {
            return [];
        }

        $user->favoriteProducts()->attach($product);

        return [];
    }

    public function disfavor(Product $product,Request $request)
    {
        $user = $request->user();
        $user->favoriteProducts()->detach($product);

        return [];
    }

    public function favorites(Request $request)
    {
        $products = $request->user()->favoriteProducts()->paginate(16);

        return view('products.favorites',['products' => $products]);
    }
}
