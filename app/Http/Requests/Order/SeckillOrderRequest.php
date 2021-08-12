<?php

namespace App\Http\Requests\Order;

use App\Http\Requests\Request;
use App\Models\Order\Order;
use App\Models\Product\Product;
use App\Models\Product\Sku;
use Illuminate\Support\Facades\Redis;
use Illuminate\Validation\Rule;

class SeckillOrderRequest extends Request
{
    public function rules()
    {
        return [
            'address.province'      => 'required',
            'address.city'          => 'required',
            'address.district'      => 'required', 
            'address.address'       => 'required', 
            'address.zip'           => 'required', 
            'address.contact_name'  => 'required', 
            'address.contact_phone' => 'required',   
            'sku_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!$user = \Auth::user()) {
                        throw new AuthenticationException('请先登录');
                    }
                    $stock = Redis::get('seckill_sku_' . $value);
                    if (is_null($stock)) {
                        return $fail('商品不存在');
                    }
                    if ($stock < 1) {
                        return $fail('商品已售完');
                    }
                    if (!$sku = Sku::find($value)) {
                        return $fail('商品不存在');
                    }
                    if ($sku->product->seckill->is_before_start) {
                        return $fail('商品秒杀未开始');
                    }
                    if ($sku->product->seckill->is_after_end) {
                        return $fail('商品秒杀已结束');
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
