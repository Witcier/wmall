<?php

namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Models\User\Address;
use Auth;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Jobs\RefundInstallmentOrder;
use App\Models\Coupon\Code;
use App\Models\Order\Order;
use App\Models\Product\Sku;
use App\Services\CartService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;

class OrderService
{
    public function store(Address $address, $remark, $items, Code $code = null)
    {
        $user = Auth::user();

        if ($code) {
            $code->checkAvailable($user);
        }

        $order = \DB::transaction(function () use ($user, $address, $remark, $items, $code) {
            $address->update([
                'last_used_at' => Carbon::now(),
            ]);
            $addressData = [ // 将地址信息放入订单中
                'address' => $address->full_address,
                'zip' => $address->zip,
                'contact_name' => $address->contact_name,
                'contact_phone' => $address->contact_phone,
            ];
            $order = new Order([
                'address' => $addressData,
                'remark' => $remark,
                'total_amount' => 0,
                'type' => Order::TYPE_NORMAL,
            ]);

            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;
            foreach ($items as $data) {
                $sku = Sku::find($data['sku_id']);

                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();

                $totalAmount += $sku->price * $data['amount'];

                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('商品库存不足');
                }
            }

            if ($code) {
                $code->checkAvailable($user, $totalAmount);
                $totalAmount = $code->getAdjustedPrice($totalAmount);
                $order->couponCode()->associate($code);

                if ($code->changeUsed() <= 0) {
                    throw new CouponCodeUnavailableException('该优惠券已被兑完');
                }
            }

            $order->update([
                'total_amount' => $totalAmount,
            ]);

            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });

        CloseOrder::dispatch($order)->delay(config('app.order_ttl'));

        return $order;
    }

    public function crowdfunding(Address $address, Sku $sku, $amount)
    {
        $user = Auth::user();

        $order = \DB::transaction(function () use ($user, $address, $sku, $amount) {
            $address->update([
                'last_used_at' => Carbon::now(),
            ]);

            $addressData = [ // 将地址信息放入订单中
                'address' => $address->full_address,
                'zip' => $address->zip,
                'contact_name' => $address->contact_name,
                'contact_phone' => $address->contact_phone,
            ];

            $order = new Order([
                'address' => $addressData,
                'remark' => '',
                'total_amount' => $sku->price * $amount,
                'type' => Order::TYPE_CROWDFUNDING,
            ]);

            $order->user()->associate($user);
            $order->save();

            $item = $order->items()->make([
                'amount' => $amount,
                'price' => $sku->price,
            ]);
            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            if ($sku->decreaseStock($amount) <= 0) {
                throw new InvalidRequestException('商品库存不足');
            }

            return $order;
        });

        $crowdfundingTtl = $sku->product->crowdfunding->end_at->getTimestamp() - time();

        CloseOrder::dispatch($order)->delay($crowdfundingTtl);

        return $order;
    }

    public function seckill(array $addressData, Sku $sku)
    {
        $user = Auth::user();

        $order = \DB::transaction(function () use ($user, $addressData, $sku) {

            $addressData = [ // 将地址信息放入订单中
                'address' => $addressData['province'] . $addressData['city'] . $addressData['district'] . $addressData['address'],
                'zip' => $addressData['zip'],
                'contact_name' => $addressData['contact_name'],
                'contact_phone' => $addressData['contact_phone'],
            ];

            $order = new Order([
                'address' => $addressData,
                'remark' => '',
                'total_amount' => $sku->price,
                'type' => Order::TYPE_SECKILL,
            ]);

            $order->user()->associate($user);
            $order->save();

            $item = $order->items()->make([
                'amount' => 1,
                'price' => $sku->price,
            ]);
            $item->product()->associate($sku->product_id);
            $item->productSku()->associate($sku);
            $item->save();

            if ($sku->decreaseStock(1) <= 0) {
                throw new InvalidRequestException('商品库存不足');
            }

            Redis::decr('seckill_sku_' . $sku->id);

            return $order;
        });

        CloseOrder::dispatch($order)->delay(config('app.seckill_order_ttl'));

        return $order;
    }

    public function refundOrder(Order $order)
    {
        switch ($order->payment_method) {
            case '1':
                $refundNo = Order::findAvailableRefundNo();

                $ret = app('alipay')->refund([
                    'out_trade_no' => $order->no,
                    'refund_amount' => $order->total_amount,
                    'out_request_no' => $refundNo,
                ]);

                if ($ret->sub_code) {
                    $extra = $order->extra;
                    $extra['refund_failed_code'] = $ret->sub_code;

                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                        'extra' => $extra, 
                    ]);
                } else {
                    $order->update([
                        'refund_no' => $refundNo,
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;

            case '2':
                $refundNo = Order::findAvailableRefundNo();

                app('wechat_pay')->refund([
                    'out_trade_no' => $order->no,
                    'total_free' => $order->total_amount * 100,
                    'refund_free' => $order->total_amount * 100,
                    'out_refund_no' => $refundNo,
                    'notify_url' => ngrok_url('payment.wechat.refund_notify'),
                ]);

                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            
            case '3':
                $refundNo = Order::findAvailableRefundNo();

                $order->update([
                    'refund_no' => $refundNo,
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);

                RefundInstallmentOrder::dispatch($order);
                break;
            default:
                throw new InternalException('未知订单支付方式：'.$order->payment_method);
                break;
        }
    }
}