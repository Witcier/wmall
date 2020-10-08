<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\ProductSku;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartSerivce;

     // 利用 Laravel 的自动解析功能注入 CartService 类
    public function __construct(CartService $cartService)
    {
        $this->cartSerivce = $cartService;
    }

    public function index(Request $request)
    {
        $cartItems = $this->cartSerivce->get();
        $addresses = $request->user()->addresses()->orderBy('last_used_at','desc')->get();

        return view('cart.index',[
            'cartItems' => $cartItems,
            'addresses' => $addresses,
        ]);
    }

    public function add(AddCartRequest $request)
    {
        $this->cartSerivce->add($request->input('sku_id'),$request->input('amount'));

        return [];
    }

    public function remove(ProductSku $sku,Request $request)
    {
        $this->cartSerivce->remove($sku->id);

        return [];
    }
}
