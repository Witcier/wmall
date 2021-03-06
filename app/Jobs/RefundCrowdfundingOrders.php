<?php

namespace App\Jobs;

use App\Models\CrowdfundingProduct;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefundCrowdfundingOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $crowdfunding;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CrowdfundingProduct $crowdfunding)
    {
        $this->crowdfunding = $crowdfunding;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 判断众筹商品的状态是否是失败，不是失败则不执行退款，这一步只是增加系统的健壮性
        if ($this->crowdfunding->status !== CrowdfundingProduct::STATUS_FAIL) {
            return;
        }

        $orderService = app(OrderService::class);
        // 查看所有该众筹商品的订单
        Order::query()
            // 订单类型为众筹订单
            ->where('type', Order::TYPE_CROWDFUNDING)
            // 已经支付的订单
            ->whereNotNull('paid_at')
            ->whereHas('items', function ($query) {
                // 包含该众筹商品的
                $query->where('product_id', $this->crowdfunding->product_id);
            })
            ->get()
            ->each(function (Order $order) use ($orderService) {
                // 调用退款逻辑
                $orderService->refundOrder($order);
            });
    }
}
