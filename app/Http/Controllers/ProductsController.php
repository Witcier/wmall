<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
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
        ]);
    }

    public function show(Product $product,Request $request)
    {
        //判断商品是否上架
        if (!$product->status) {
            throw new \Exception('商品未上架');
        }

        return view('products.show',[
            'product' => $product
        ]);
    }
}
