<?php

namespace App\Services;

use App\Events\OrderReviewed;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InternalException;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrder;
use App\Models\CouponCode;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;

class OrderService
{
    public function store(User $user, UserAddress $address, $remark, $items, CouponCode $coupon = null)
    {
        // 如果传入了优惠卷，先检测是否可用
        if ($coupon) {
            $coupon->checkAvailable($user);
        }
        $order = \DB::transaction(function () use ($user, $address, $remark, $items, $coupon) {
            // 更新此地址的最后使用时间
            $address->update(['last_used_at' => Carbon::now()]);
            // 创建一个订单
            $order = new Order([
                'address' => [
                    // 将地址信息放入订单中
                    'address' => $address->full_address,
                    'zip' => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => $remark,
                'total_amount' => 0,
                'type' => Order::TYPE_NORMAL,
            ]);
            // 订单关联到当前用户
            $order->user()->associate($user);
            // 写入数据库
            $order->save();

            $totalAmount = 0;
            // 遍历用户提交的 SKU
            foreach ($items as $data) {
                $sku  = ProductSku::find($data['sku_id']);
                // 创建一个 OrderItem 并直接与当前订单关联
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('该商品库存不足');
                }
            }

            if ($coupon) {
                // 检测优惠卷
                $coupon->checkAvailable($user, $totalAmount);
                // 修改订单的总金额
                $totalAmount = $coupon->getAdjustedPrice($totalAmount);
                // 将订单与优惠卷关联
                $order->couponCode()->associate($coupon);
                // 增加优惠卷的使用数量
                if ($coupon->changeUsed() <= 0) {
                    throw new CouponCodeUnavailableException('该优惠券已被兑完');
                }
            }

            // 更新订单总金额
            $order->update(['total_amount' => $totalAmount]);

            // 将下单的商品从购物车中移除
            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });

        // 这里我们直接使用 dispatch 函数
        dispatch(new CloseOrder($order,config('app.order_ttl')));

        return $order;
    }

    public function sendReview(Order $order, $reviews)
    {
        // 开启事务
        \DB::transaction(function () use ($reviews, $order) {
            // 遍历用户提交的数据
            foreach ($reviews as $review) {
                $orderItem = $order->items()->find($review['id']);

                // 保存评价和评分
                $orderItem->update([
                    'rating' => $review['rating'],
                    'review' => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }

            // 将订单改为已经评价
            $order->update(['reviewed' => true]);

            event(new OrderReviewed($order));
        });
    }

    public function applyRefund(Order $order, $reason)
    {
        // 将用户输入的退款理由放到订单的 extra 字段
        $extra = $order->extra ?: [];
        $extra['refund_reason'] = $reason;

        // 将订单的退款状态改为申请退款
        $order->update([
            'refund_status' => Order::REFUND_STATUS_APPLIED,
            'extra'         => $extra,
        ]);

        return $order;
    }

    // 众筹商品下单
    public function crowdfunding(User $user, UserAddress $address, ProductSku $sku, $amount)
    {
        // 开启事务
        $order = \DB::transaction(function () use ($amount,$sku, $address, $user) {
            // 更新地址的最后使用时间
            $address->update(['last_used_at' => Carbon::now()]);

            // 创建订单
            $order = new Order([
                'address' => [
                    'address' => $address->full_address,
                    'zip' => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => '',
                'total_amount' => $sku->price * $amount,
                'type' => Order::TYPE_CROWDFUNDING,
            ]);

            // 将订单关联到当前用户
            $order->user()->associate($user);

            // 保存订单
            $order->save();

            // 创建一个订单详情与 sku 关联
            $item = $order->items()->make([
                'amount' => $amount,
                'price' => $sku->price,
            ]);
            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            // 减去 sku 的库存
            if ($sku->decreaseStock($amount) <= 0) {
                throw new InvalidRequestException('该商品库存不足');
            }

            return $order;
        });

        // 众筹结束时间减去当前时间得到剩余秒数
        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();
        // 剩余秒数与默认订单关闭时间取小值作为订单关闭时间
        dispatch(new CloseOrder($order, min(config('app.order_ttl'),$crowdfundingTtl)));

        return $order;
    }

    public function refundOrder(Order $order)
    {
        // 判断该订单的支付方式
        switch ($order->payment_method) {
            case 'wechat':
                // 生成退款订单号
                $refundNo = Order::getAvailableRefundNo();
                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no,
                    'total_fee' => $order->total_amount * 100,
                    'refund_fee' => $order->total_amount * 100,
                    'out_refund_no' => $refundNo,
                    // 微信支付的退款结果并不是实时返回的，而是通过退款回调来通知，因此这里需要配上退款回调接口地址
                    // 'notify_url' => route('payment.wechat.refund_notify'),
                    'notify_url' => ngrok_url('payment.wechat.refund_notify'),
                ]);
                // 将订单的退款状态该为退款中
                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                // 生成退款订单号
                $refundNo = Order::getAvailableRefundNo();
                // 调用支付宝的支付实例的 refund 方法
                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no,
                    'refund_amount' => $order->total_amount,
                    'out_request_no' => $refundNo,
                ]);
                    // 根据支付宝的文档，如果返回值里有 sub_code 字段说明退款失败
                    if ($ret->sub_code) {
                        // 将退款失败的保存存入 extra 字段
                        $extra = $order->extra;
                        $extra['refund_failed_code'] = $ret->sub_code;
                        // 将订单的退款状态标记为退款失败
                        $order->update([
                            'refund_no' => $refundNo,
                            'refund_status' => Order::REFUND_STATUS_FAILED,
                            'extra' => $extra,
                        ]);
                    } else {
                        // 将订单的退款状态标记为退款成功
                        $order->update([
                            'refund_no' => $refundNo,
                            'refund_status' => Order::REFUND_STATUS_SUCCESS,
                        ]);
                    }
                break;
            case 'installment':
                $order->update([
                    'refund_no' => Order::getAvailableRefundNo(),
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);

                // 触发退款异步任务
                dispatch(new RefundInstallmentOrder($order));
                break;
            default:
                throw new InternalException('未知支付方式：'.$order->payment_method);
                break;
        }
    }

    public function seckill(User $user, UserAddress $address, ProductSku $sku)
    {
        $order = \DB::transaction(function () use ($user, $address, $sku) {
            // 更新地址的使用时间
            $address->update(['last_used_at' => Carbon::now()]);

            // 扣除对应的 sku 数量
            if ($sku->decreaseStock(1) <= 0) {
                throw new InvalidRequestException('该商品库存不足');
            }

            // 创建一个订单
            $order = new Order([
                'address' => [
                    'address' => $address->full_address,
                    'zip' => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => '',
                'total_amount' => $sku->price,
                'type' => Order::TYPE_SECKILL,
            ]);

            // 订单关联到当前用户
            $order->user()->associate($user);
            //  写人数据库
            $order->save();

            // 创建一个新的订单详情与 sku 关联
            $item = $order->items()->make([
                'amount' => 1,
                'price' => $sku->price,
            ]);

            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            return $order;
        });

        // 秒杀订单的自动关闭和普通的订单不一样
        dispatch(new CloseOrder($order, config('app.seckill_order_ttl')));

        return $order;
    }
}
