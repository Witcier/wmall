<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\SearchBuilders\ProductSearchBuilder;
use App\Services\CategoryService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductsController extends Controller
{
    public function index(Request $request)
    {
        $page    = $request->input('page', 1);
        $perPage = 16;

        $builder = (new ProductSearchBuilder())->onSale()->paginate($perPage, $page);

         // 分类筛选
        if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
            // 调用查询构造器的分类筛选
            $builder->category($category);
        }

        // 关键字搜索
        if ($search = $request->input('search','')) {
            // 将搜索关键字根据空格拆分成数组， 并过滤掉空项
            $keywords = array_filter(explode(' ', $search));

            // 调用查询构造器的关键字搜索
            $builder->keywords($keywords);
        }

        // 分面搜索, 只有用户输入了搜索关键字或者选择了分类才会执行
        if ($search || isset($category)) {
           // 调用查询构造器的分面搜索
           $builder->aggregateProperties();
        }

        $propertyFilters = [];
        // 从用户请求参数获取 filters
        if ($filterString = $request->input('filters')) {
            // 将获取到字符串用字符 | 拆分成数组
            $filterArray = explode('|', $filterString);

            foreach ($filterArray as $filter) {
                // 将字符用符号 ： 拆分两部分并且赋值给 $name 和 $value 两个变量
                list($name, $value) = explode(':', $filter);

                // 将用户筛选的属性加入到数组中
                $propertyFilters[$name] = $value;

                // 调用查询构造器的属性筛选
                $builder->propertyFilter($name, $value);
            }
        }

        // 是否有提交 order 参数，如果有就赋值给 $order 变量
        // order 参数用来控制商品的排序规则
        if ($order = $request->input('order', '')) {
            // 是否是以 _asc 或者 _desc 结尾
            if (preg_match('/^(.+)_(asc|desc)$/', $order, $m)) {
                // 如果字符串的开头是这 3 个字符串之一，说明是一个合法的排序值
                if (in_array($m[1], ['price', 'sold_count', 'rating'])) {
                    // 根据传入的排序值来构造排序参数
                    $params['body']['sort'] = [[$m[1] => $m[2]]];
                }
            }
        }

        $result = app('es')->search($builder->getParams());

        // 通过 collect 函数将返回结果转为集合，并通过集合的 pluck 方法取到返回的商品 ID 数组
        $productIds = collect($result['hits']['hits'])->pluck('_id')->all();

        // 通过 whereIn 方法从数据库中读取商品数据
        $products = Product::query()
            ->whereIn('id', $productIds)
            ->orderByRaw(sprintf("FIND_IN_SET(id, '%s')", join(',', $productIds)))
            ->get();
        // 返回一个 LengthAwarePaginator 对象
        $pager = new LengthAwarePaginator($products, $result['hits']['total']['value'], $perPage, $page, [
            'path' => route('products.index', false), // 手动构建分页的 url
        ]);

        $properties = [];

        // 如果返回结果里有 aggregations 字段， 说明做了分面搜索
        if (isset($result['aggregations'])) {
            // 使用 collect 函数将返回值转换为集合
            $properties = collect($result['aggregations']['properties']['properties']['buckets'])
                ->map(function ($bucket) {
                    // 通过 map 方法取出我们需要的字段
                    return [
                        'key' => $bucket['key'],
                        'values' => collect($bucket['value']['buckets'])->pluck('key')->all(),
                    ];
                })
                ->filter(function ($property) use ($propertyFilters) {
                    // 过滤掉只剩下一个值 或者 已经在筛选条件里的属性
                    return count($property['values']) > 1 && !isset($propertyFilters[$property['key']]);
                });
        }

        return view('products.index', [
            'products' => $pager,
            'filters'  => [
                'search' => $search,
                'order'  => $order,
            ],
            'category' => $category ?? null,
            'properties' => $properties,
            'propertyFilters' => $propertyFilters,
        ]);
    }
    // public function index(Request $request, CategoryService $categoryService)
    // {
    //     //创建查询构造器
    //     $builder = Product::query()->where('status',true);

    //     //search 参数用来模糊搜索商品
    //     if ($search = $request->input('search','')) {
    //         $like = '%'.$search.'%';

    //         $builder->where(function ($query) use ($like) {
    //             $query->where('title','like',$like)
    //                 ->orWhere('description','like','$like')
    //                 ->orWhereHas('skus',function ($query) use ($like) {
    //                     $query->where('title','like',$like)
    //                         ->orWhere('description','like',$like);
    //                 });
    //         });
    //     }

    //     // 如果有传入 category_id 字段，并且在数据库中有对应的类目
    //     if ($request->input('category_id') && $category = Category::find($request->input('category_id'))) {
    //         // 如果传入的分类是父类
    //         if ($category->is_directory) {
    //             // 筛选出该父类下的所有子分类的商品
    //             $builder->whereHas('category', function ($query) use ($category) {
    //                 $query->where('path','like',$category->path.$category->id.'-%');
    //             });
    //         } else {
    //             // 如果不是父类
    //             $builder->where('category_id', $category->id);
    //         }
    //     }

    //     // order 参数控制商品的排序规则
    //     if ($order = $request->input('order','')) {
    //         if (preg_match('/^(.+)_(asc|desc)$/',$order,$m)) {
    //             if (in_array($m[1],['price','sold_count','rating'])) {
    //                 $builder->orderBy($m[1],$m[2]);
    //             }
    //         }
    //     }

    //     $products = $builder->paginate(16);

    //     return view('products.index',[
    //         'products' => $products,
    //         'filters' =>[
    //             'search' => $search,
    //             'order' => $order,
    //         ],
    //         'category' => $category ?? null,
    //         'categoryTree' => $categoryService->getCategoryTree(),
    //     ]);
    // }

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
