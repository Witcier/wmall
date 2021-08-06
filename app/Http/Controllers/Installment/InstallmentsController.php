<?php

namespace App\Http\Controllers\Installment;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Models\Installment\Installment;
use App\Models\Order\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id', $request->user()->id)
            ->paginate(10);

        return view('installments.index', [
            'installments' => $installments,
        ]);
    }

    public function show(Installment $installment)
    {
        $this->authorize('own', $installment);

        $items = $installment->items()->orderBy('sequence')->get();

        return view('installments.show', [
            'installment' => $installment,
            'items' => $items,
            'nextItem' => $items->where('paid_at', null)->first(),
        ]);
    }

    public function payByAlipay(Installment $installment)
    {
        if ($installment->order->closed) {
            throw new InvalidRequestException('订单已关闭');
        }

        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('分期付款订单已结清');
        }

        if (!$nextItem = $installment->items()->where('paid', false)->orderBy('sequence')->first()) {
            throw new InvalidRequestException('分期付款订单已结清');
        }

        return app('alipay')->web([
            'out_trade_no' => $installment->no . '_' . $nextItem->sequence,
            'total_amount' => $nextItem->total,
            'subject' => '支付 Shop 的分期订单：'.$installment->no,
            'notify_url' => ngrok_url('installments.alipay.notify'),
            'return_url' => route('installments.alipay.return'),
        ]);
    }

    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('errors.error', [
                'msg' => '数据不正确',
            ]);
        }

        return view('success.success', [
            'msg' => '付款成功',
        ]);
    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();

        
        if (!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        if ($this->paid($data->out_trade_no, Order::PAYMENT_METHOD_ALIPAY, $data->trade_no  )) {
            return app('alipay')->success();
        }

        return 'fail';
    }

    public function payByWechat(Installment $installment)
    {
        if ($installment->order->closed) {
            throw new InvalidRequestException('订单已关闭');
        }

        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('分期付款订单已结清');
        }

        if (!$nextItem = $installment->items()->where('paid', false)->orderBy('sequence')->first()) {
            throw new InvalidRequestException('分期付款订单已结清');
        }

        $wechatOrder = app('wechat_pay')->scan([
            'out_trade'  => $installment->no . '_' . $nextItem->sequence,
            'total_free' => $nextItem->total * 100,
            'body'       => '支付 Shop 的分期订单：' . $installment->no,
            'notify_url' => ngrok_url('installments.wechat.notify')
        ]);

        $qrCode = new QrCode($wechatOrder->code_url);

        return response($wechatOrder->writeString(), 200, [
            'Content-Type' => $qrCode->getContentType(),
        ]);
    }

    public function wechatNotify()
    {
        $data = app('wechat_pay')->verify();

        if ($this->paid($data->out_trade_no, Order::PAYMENT_METHOD_WECHAT, $data->transaction_id)) {
            return app('wechat_pay')->success();
        }

        return 'fail';
    }

    protected function paid($outTradeNo, $paymentMethod, $paymentNo)
    {
        list($no, $sequence) = explode('_', $outTradeNo);
        $installment = Installment::where('no', $no)->first();

        if (!$installment) {
            return false;
        }

        if (!$item = $installment->items()->where('sequence', $sequence)->first()) {
            return false;
        }

        if ($item->paid) {
            return app('alipay')->success();
        }

        \DB::transaction(function () use ($paymentNo, $paymentMethod, $no, $installment, $item) {
            $item->update([
                'paid' => true,
                'paid_at' => Carbon::now(),
                'payment_method' => $paymentMethod,
                'payment_no' => $paymentNo,
            ]);

            if ($item->sequence === 0) {
                $installment->update([
                    'status' => Installment::STATUS_REPAYING,
                ]);

                $installment->order->update([
                    'paid' => true,
                    'paid_at' => Carbon::now(),
                    'payment_method' => Order::PAYMENT_METHOD_INSTALLMENT,
                    'payment_no' => $no,
                ]);

                event(new OrderPaid($installment->order));
            }

            if ($item->sequence === $installment->count - 1) {
                $installment->update([
                    'status' => Installment::STATUS_FINISHED,
                ]);
            }
        });

        return true;
    }
}
