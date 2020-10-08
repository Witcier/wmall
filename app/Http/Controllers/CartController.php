<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $cartItems = $request->user()->cartItems()->with(['productSku.product'])->get();
        $addresses = $request->user()->addresses()->orderBy('last_used_at','desc')->get();

        return view('cart.index',[
            'cartItems' => $cartItems,
            'addresses' => $addresses,
        ]);
    }

    public function add(AddCartRequest $requset)
    {
        $user = $requset->user();
        $skuId = $requset->input('sku_id');
        $amount = $requset->input('amount');

        //查询商品是否已经存在购物车
        if ($cart = $user->cartItems()->where('product_sku_id',$skuId)->first()) {
            //如果存在叠加数量
            $cart->update([
                'amount' => $cart->amount + $amount,
            ]);
        } else {
            //创建一个新的购物车记录
            $cart = new CartItem(['amount' => $amount]);
            $cart->user()->associate($user);
            $cart->productSku()->associate($skuId);
            $cart->save();
        }

        return [];
    }

    public function remove(ProductSku $sku,Request $request)
    {
        $request->user()->cartItems()->where('product_sku_id',$sku->id)->delete();

        return [];
    }
}