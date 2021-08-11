<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\Request;
use App\Models\Order\Order;
use App\Models\Product\Product;
use App\Models\Product\Sku;
use Illuminate\Validation\Rule;

class SeckillOrderRequest extends Request
{
    public function rules()
    {
        return [
            'address_id' => [
                'required',
                Rule::exists('user_addresses', 'id')->where('user_id', $this->user()->id),
            ],
            'sku_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$sku = Sku::find($value)) {
                        return $fail('商品不存在');
                    }
                    if ($sku->product->type !== Product::TYPE_SECKILL) {
                        return $fail('商品不支持秒杀');
                    }
                    if (!$sku->product->on_sale) {
                        return $fail('商品不存在');
                    }
                    if ($sku->product->seckill->is_before_start) {
                        return $fail('商品秒杀未开始');
                    }
                    if ($sku->product->seckill->is_after_end) {
                        return $fail('商品秒杀已结束');
                    }
                    if ($sku->stock == 0) {
                        return $fail('商品已售完');
                    }
                    
                    if ($order = Order::query()
                        ->where('user_id', $this->user()->id)
                        ->whereHas('items', function ($query) use ($value) {
                            $query->where('product_sku_id', $value);
                        })
                        ->where(function ($query) {
                            $query->where('paid', true)
                                ->orWhere('closed', false);
                        })
                        ->first()) {
                        if ($order->paid) {
                            return $fail('你已经抢购了该商品');
                        }

                        return $fail('该商品已经下单了，请到订单页面进行付款');
                    }
                } 
            ],
        ];
    }
}
