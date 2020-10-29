<?php

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Models\Order;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateCrowdfundingProductProgress
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderPaid  $event
     * @return void
     */
    public function handle(OrderPaid $event)
    {
        $order = $event->getOrder();

        // 判断是否众筹商品订单
        if ($order->type !== Order::TYPE_CROWDFUNDING) {
            return;
        }

        $crowdfunding = $order->item[0]->product->crowdfunding;

        $data = Order::query()
            // 查出众筹商品订单
            ->where('type', Order::TYPE_CROWDFUNDING)
            // 以及已支付的
            ->whereNotNull('paid_at')
            ->whereHas('items', function ($query) use ($crowdfunding) {
                // 包含本商品的
                $query->where('product_id', $crowdfunding->product_id);
            })
            ->first([
                // 取出订单的总金额
                \DB::raw('sum(total_amount) as total_amount'),
                // 取出去重的支持用户数
                \DB::raw('count(distinct(user_id)) as user_count'),
            ]);

        $crowdfunding->update([
            'total_amount' => $data->total_amount,
            'user_count' => $data->user_count,
        ]);
    }
}
