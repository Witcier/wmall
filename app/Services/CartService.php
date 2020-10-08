<?php

namespace App\Services;

use Auth;
use App\Models\CartItem;

class CartService
{
    public function get()
    {
        return Auth::user()->cartItems()->with(['ProductSku.product'])->get();
    }

    public function add($skuId, $amount)
    {
        $user = Auth::user();
        // 查询商品是否已经存在购物车
        if ($item = $user->cartItems()->where('product_sku_id', $skuId)->first()) {
            // 如果存在叠加数量
            $item->update([
                'amount' => $item->amount + $amount,
            ]);
        } else {
            // 创建一个新的购物车记录
            $item = new CartItem(['amount' => $amount]);
            $item->user()->associate($user);
            $item->productSku()->associate($skuId);
            $item->save();
        }

        return $item;
    }

    public function remove($skuIds)
    {
        // 可以传单个 ID，也可以传 ID 数组
        if (!is_array($skuIds)) {
            $skuIds = [$skuIds];
        }

        Auth::user()->cartItems()->whereIn('product_sku_id', $skuIds)->delete();
    }
}