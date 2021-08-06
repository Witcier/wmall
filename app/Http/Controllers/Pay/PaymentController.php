<?php

namespace App\Http\Controllers\Pay;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Models\Installment\Installment;
use App\Models\Order\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        return app('alipay')->web([
            'out_trade_no' => $order->no,
            'total_amount' => $order->total_amount,
            'subject'      => '支付 Shop 的订单：' . $order->no,
        ]);
    }

    public function alipayReturn()
    {
        try {
            $data = app('alipay')->verify();
        } catch (\Exception $e) {
            return view('errors.error', [
                'msg' => '数据不准确',
            ]);
        }

        return view('success.success',[
            'msg' => '付款成功'
        ]);
    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();

        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        $order = Order::where('no', $data->out_trade_no)->first();

        if (!$order) {
            return 'fail';
        }

        if ($order->paid) {
            return app('alipay')->success();
        }

        $order->update([
            'paid' => true,
            'paid_at' => Carbon::now(),
            'payment_method' => Order::PAYMENT_METHOD_ALIPAY,
            'payment_no' => $data->trade_no,
        ]);

        $this->afterPaid($order);
        
        return app('alipay')->success();
    }

    public function payByWechat(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        $wechatOrder = app('wechat_pay')->scan([
            'out_trade'  => $order->no,
            'total_free' => $order->total_amount * 100,
            'body'       => '支付 Shop 的订单：' . $order->no,
        ]);

        $qrCode = new QrCode($wechatOrder->code_url);

        return response($wechatOrder->writeString(), 200, [
            'Content-Type' => $qrCode->getContentType(),
        ]);
    }

    public function wechatNotify()
    {
        $data = app('wechat_pay')->verify();

        $order = Order::where('no', $data->out_trade_no)->first();

        if (!$order) {
            return 'fail';
        }

        if ($order->paid) {
            return app('wechat_pay')->success();
        }

        $order->update([
            'paid' => true,
            'paid_at' => Carbon::now(),
            'payment_method' => Order::PAYMENT_METHOD_WECHAT,
            'payment_no' => $data->transaction_id,
        ]);

        $this->afterPaid($order);

        return app('wechat_pay')->success();
    }

    public function payByInstallment(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        if ($order->total_amount < config('installment.min_installment_amount')) {
            throw new InvalidRequestException('订单少于' . config('installment.min_installment_amount') . '不能分期付款');
        }

        $this->validate($request, [
            'count' => [
                'required',
                Rule::in(array_keys(config('installment.installment_fee_rate'))),
            ],
        ]);

        $count = $request->input('count');
        $user = $request->user();

        $installment= \DB::transaction(function () use ($order, $count, $user){
            Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();

            $installment = new Installment([
                'total_amount' => $order->total_amount,
                'count' => $count,
                'fee_rate' => config('installment.installment_fee_rate')[$count],
                'fine_rate' => config('installment.installment_fine_rate'),
            ]);

            $installment->user()->associate($user);
            $installment->order()->associate($order);
            $installment->save();

            $dueAt = Installment::getFirstDueAt();

            $base = big_number($order->total_amount)->divide($count)->getValue();
            $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();

            for ($i = 0; $i < $count; $i++) { 
                // 最后一期
                if ($i === $count - 1) {
                    $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1));
                }

                $installment->items()->create([
                    'sequence' => $i,
                    'base' => $base,
                    'fee' => $fee,
                    'due_at' => $dueAt,
                ]);

                $dueAt = $dueAt->copy()->addMonth();
            }

            return $installment;
        });

        return $installment;
    }

    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function wechatRefundNotify(Request $request)
    {
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';

        $data = app('wechat_pay')->verify(null, true);

        $order = Order::where('no', $data['out_trade_no'])->first();

        if (!$order) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            $order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        } else {
            $extra = $order->extra;
            $extra['refund_failed_code'] = $data['refund_status'];

            $order->update([
                'refund_status' => Order::REFUND_STATUS_FAILED,
                'extra' => $extra, 
            ]);
        }

        return app('wechat_pay')->success();
    }
}
