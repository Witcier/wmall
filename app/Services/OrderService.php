<?php

namespace App\Services;

use App\Models\User\Address;
use Auth;
use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\Order\Order;
use App\Models\Product\Sku;
use App\Services\CartService;
use Carbon\Carbon;

class OrderService
{
    public function store(Address $address, $remark, $items)
    {
        $user = Auth::user();

        $order = \DB::transaction(function () use ($user, $address, $remark, $items) {
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

            $order->update([
                'total_amount' => $totalAmount,
            ]);

            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);

            return $order;
        });

        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }
}