<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateCrowdfundingProductProgress implements ShouldQueue
{
    public function handle(OrderPaid $event)
    {
        $order = $event->getOrder();

        if ($order->type !== Order::TYPE_CROWDFUNDING) {
            return;
        }

        $crowdfunding = $order->items[0]->product->crowdfunding;

        $data = Order::query()
            ->where('type', Order::TYPE_CROWDFUNDING)
            ->where('paid', true)
            ->whereHas('items', function ($query) use ($crowdfunding) {
                $query->where('product_id', $crowdfunding->product_id);
            })
            ->first([
                \DB::raw('sum(total_amount) as total_amount'),
                \DB::raw('count(distinct(user_Id)) as user_count'),
            ]);

        $crowdfunding->update([
            'total_amount' => $data->total_amount,
            'user_count' => $data->user_count,
        ]);
    }
}
