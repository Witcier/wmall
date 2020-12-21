<?php

namespace App\Http\Requests;

use App\Exceptions\InvalidRequestException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product;
use App\Models\ProductSku;
use App\Models\Order;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Redis;

class SeckillOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'address.province' => 'required',
            'address.city' => 'required',
            'address.district' => 'required',
            'address.address' => 'required',
            'address.zip' => 'required',
            'address.contact_name' => 'required',
            'address.contact_phone' => 'required',
            'sku_id' => [
                'required',
                function ($attribute, $value, $fail) {
                    // 从 Redis 读取数据
                    $stock = Redis::get('seckill_sku_'.$value);
                    if (is_null($stock)) {
                        return $fail('该商品不存在');
                    }
                    if ($stock < 1) {
                        return $fail('商品已售完');
                    }

                    $sku = ProductSku::find($value);
                    if ($sku->product->type !== Product::TYPE_SECKILL) {
                        return $fail('该商品不支持秒杀');
                    }
                    if ($sku->product->seckill->is_before_start) {
                        return $fail('秒杀未开始');
                    }
                    if ($sku->product->seckill->is_after_start) {
                        return $fail('秒杀已结束');
                    }
                    if (!$sku->product->status) {
                        return $fail('该商品未上架');
                    }

                    if (!$user = \Auth::user()) {
                        throw new AuthenticationException('请先登录');
                    }
                    // if (!$user->email_verified_at) {
                    //     throw new InvalidRequestException('请先验证邮箱');
                    // }
                    if ($order = Order::query()
                        // 筛选出当前用户的订单
                        ->where('user_id', $this->user()->id)
                        ->whereHas('items', function ($query) use ($value) {
                            // 筛选出包含该 sku 的订单
                            $query->where('product_sku_id', $value);
                        })
                        ->where(function ($query) {
                            // 已经支付的订单
                            $query->whereNotNull('paid_at')
                                // 或者未关闭的订单
                                ->orWhere('closed', false);
                        })
                        ->first()) {
                        if ($order->paid_at) {
                            return $fail('你已经抢购了该商品');
                        }

                        return $fail('你已经抢购了该商品，请到订单页面付款');
                    }
                }
            ],
        ];
    }
}
