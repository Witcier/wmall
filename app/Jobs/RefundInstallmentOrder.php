<?php

namespace App\Jobs;

use App\Exceptions\InternalException;
use App\Models\Order;
use App\Models\Installment;
use App\Models\InstallmentItem;
use Illuminate\Bus\Queueable;
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
        // 如果订单不是分期返款订单，未支付的，退款状态为退款中
        if ($this->order->payment_method !== 'installment' || !$this->order->paid_at || $this->order->refund_status !== Order::REFUND_STATUS_PROCESSING) {
            return;
        }

        // 增加系统的健壮性
        if (!$installment = Installment::query()->where('order_id', $this->order->id)->first()) {
            return;
        }

        // 遍历对应的分期付款的所有还款计划
        foreach ($installment->items as $item) {
            // 如果还款计划未支付，或者退款状态退款成功或者退款中
            if (!$item->paid_at || in_array($item->refund_status, [
                InstallmentItem::REFUND_STATUS_PROCESSING,
                InstallmentItem::REFUND_STATUS_SUCCESS,
            ])) {
                continue;
            }

            // 调用具体的退款逻辑
            try {
                $this->refundInstallmentItem($item);
            } catch (\Exception $e) {
                \Log::warning('分期退款失败:'.$e->getMessage(), [
                    'installment_item_id' => $item->id,
                ]);

                // 假如某个还款计划报错了，则暂时跳过，继续处理下一个还款计划的退款
                continue;
            }
        }

        $installment->refreshRefundStatus();
    }

    protected function refundInstallmentItem(InstallmentItem $item)
    {
        // 退款订单号使用订单的退款号和还款计划的序号拼接
        $refundNo = $this->order->refund_no.'_'.$item->sequence;

        // 根据还款计划的支付方式执行相应的退款逻辑
        switch ($item->payment_method) {
            case 'wechat':
                app('wechat_pay')->refund([
                    'transaction_id' => $item->payment_no,
                    'total_fee' => $item->total * 100,
                    'refund_fee' => $item->base * 100,
                    'out_refund_no' => $refundNo,
                    'notify_url' => ngrok_url('installments.wechat.refund_notify'),
                ]);

                // 将还款计划的退款状态改为退款中
                $item->update([
                    'refund_status' => InstallmentItem::REFUND_STATUS_PROCESSING,
                ]);
                break;
            case 'alipay':
                $ret = app('alipay')->refund([
                    'trade_no' => $item->payment_no,
                    'refund_amount' => $item->base,
                    'out_request_no' => $refundNo,
                ]);

                // 根据支付宝的文档，如果返回值有 sub_code 字段说明退款失败
                if ($ret->sub_code) {
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_FAILED,
                    ]);
                } else {
                    // 改为退款成功
                    $item->update([
                        'refund_status' => InstallmentItem::REFUND_STATUS_SUCCESS,
                    ]);
                }
                break;
            default:
                // 如果进入这里
                throw new InternalException('未知支付方式：'.$item->payment_method);
                break;
        }
    }
}
