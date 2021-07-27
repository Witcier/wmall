<?php

namespace App\Http\Controllers\Pay;

use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Models\Order\Order;
use Carbon\Carbon;
use Illuminate\Http\Request;

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
        \Log::debug("Alipay Notify", $data->all());

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

        return app('alipay')->success();
    }
}
