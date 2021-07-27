<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order\Item;
use App\Models\Order\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateProductSoldCount implements ShouldQueue
{
    public function handle(OrderPaid $event)
    {
        $order = $event->getOrder();

        $order->load('items.product');

        foreach ($order->items as $item) {
            $product = $item->product;

            $soldCount = Item::query()
                ->where('product_id', $product->id)
                ->whereHas('order', function ($query) {
                    $query->where('paid', true);
                })
                ->sum('amount');

            $product->update([
                'sold_count' => $soldCount,
            ]);
        }
    }
}
