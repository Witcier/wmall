<?php

namespace App\Jobs;

use App\Models\Installment\Installment;
use App\Models\Installment\Item;
use App\Models\Order\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefundInstallmentOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->order->payment_method !== 3
            || !$this->order->paid
            || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING) {
            return;
        }

        \Log::info("执行item:2");
        if (!$installment = Installment::query()->where('order_id', $this->order->id)->first()) {
            return;
        }

        \Log::info("执行item:3");

        foreach ($installment->items as $item) {
            if (!$item->paid || in_array($item->refund_status, [
                Item::REFUND_STATUS_SUCCESS,
                Item::REFUND_STATUS_PROCESSING,
            ])) {
                continue;
            }

            try {
                \Log::info("执行item:". $item);
                $this->refundInstallmentItem($item);
            } catch (\Exception $e) {
                \Log::warning("分期退款失败：" . $e->getMessage(), [
                    'installment_item_id' => $item->id,
                ]);

                continue;
            }
        }

        $installment->refreshRefundStatus();
    }

    protected function refundInstallmentItem(Item $item)
    {
        $refundNo = $this->order->refund_no . '_' . $item->sequence;

        switch ($item->payment_method) {
            case Order::PAYMENT_METHOD_ALIPAY:
                $ret = app('alipay')->refund([
                    'trade_no' => $item->payment_no,
                    'refund_amount' => $item->base,
                    'out_request_no' => $refundNo,
                ]);

                if ($ret->sub_code) {
                    $item->update([
                        'refund_status' => Order::REFUND_STATUS_FAILED,
                    ]);
                } else {
                    $item->update([
                        'refund_status' => Order::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;

            case Order::PAYMENT_METHOD_WECHAT:
                app('wechat_pay')->refund([
                    'transaction_id' => $item->payment_no,
                    'total_free' => $item->total * 100,
                    'refund_free' => $item->base * 100,
                    'out_refund_no' => $refundNo,
                    'notify_url' => ngrok_url('installments.wechat.refund_notify'),
                ]);

                $item->update([
                    'refund_status' => Order::REFUND_STATUS_PROCESSING,
                ]);
                break;
            default:
                throw new InternalException('未知订单支付方式：'.$item->payment_method);
                break;
        }
    }
}
