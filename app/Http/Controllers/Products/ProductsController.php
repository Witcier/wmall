<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;

class ProductsController extends Controller
{
    public function index(Request $request)
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

        return view('products.index', [
            'products' => $builder->paginate(16),
            'filters' => [
                'order' => $order,
                'search' => $search,
            ],
        ]);
    }
}
