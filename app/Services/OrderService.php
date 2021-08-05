<?php

namespace App\Services;

use App\Exceptions\CouponCodeUnavailableException;
use App\Models\User\Address;
use Auth;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\Coupon\Code;
use App\Models\Order\Order;
use App\Models\Product\Sku;
use App\Services\CartService;
use Carbon\Carbon;

use function Laravel\Octane\Swoole\dispatch;

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
}