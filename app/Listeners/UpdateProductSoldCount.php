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
                ->where('product_id', $product)
                ->whereHas('order', function ($query) {
                    $query->whereNotNull('paid_at');
                })
                ->sum('amount');

            \Log::info("amount:".$soldCount);
            $product->update([
                'sold_count' => $soldCount,
            ]);
        }
    }
}
