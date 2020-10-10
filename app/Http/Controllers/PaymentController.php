<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own',$order);
        // 订单已支付或者已关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // 调用支付宝的网页支付
        return app('alipay')->web([
            'out_trade_no' => $order->no,
            'total_amount' => $order->total_amount,
            'subject'      => '支付 Witcier Mall 的订单：'.$order->no,
        ]);
    }

    // 前段回调页面
    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
           return view('index.error',['msg' => '数据不正确']);
        }

        return view('index.success',['msg' => '付款成功']);
    }

    // 服务端回调
    public function alipayNotify()
    {
         // 校验输入参数
         $data = app('alipay')->verify();
         // 如果订单状态不是成功或者结束，则不走后续的逻辑
         // 所有交易状态：https://docs.open.alipay.com/59/103672
         if (!in_array($data->trade_status,['TRADE_SUCCESS','TRADE_FINISHED'])) {
            return app('alipay')->success();
         }
         // $data->out_trade_no 拿到订单流水号，并在数据库中查询
         $order = Order::where('no', $data->out_trade_no)->first();
         // 正常来说不太可能出现支付了一笔不存在的订单，这个判断只是加强系统健壮性。
         if (!$order) {
             return 'fail';
         }

         // 如果这笔订单的状态已经是已支付
         if ($order->paid_at) {
             // 返回数据给支付宝
             return app('alipay')->success();
         }

         $order->update([
             'paid_at' => Carbon::now(),
             'payment' => 'alipay',
             'payment_no' => $data->trade_no,
         ]);

         return app('alipay')->success();
    }

    public function payByWechat(Order $order, Request $request)
    {
        // 校验权限
        $this->authorize('own',$order);
        // 校验订单状态
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }

        // scan 方法为微信拉起微信扫码支付
        $wechatOrder = app('wechat_pay')->scan([
            'out_trade_no' => $order->no,
            'total_fee'    => $order->total_amount * 100,
            'body'         => '支付 Witcer Mall 的订单：'.$order->no,
        ]);

        // 把要转换的字符串作为 QrCode 的构造函数
        $qrCode = new QrCode($wechatOrder->code_url);

        // 将生成的二维码图片数据以字符串形式输出，并带上相应的响应类型
        return response($qrCode->writeString(),200,['Content-Type' => $qrCode->getContentType()]);
    }

    public function wechatNotify()
    {
        // 校验回调参数是否正确
        $data = app('wechat_pay')->verify();
        // 找到对应的订单
        $order = Order::where('no',$data->out_trade_no)->first();
        // 订单不存在
        if (!$order) {
            return 'fail';
        }
        // 订单已支付
        if ($order->paid_at) {
            // 告知微信支付该订单已经处理
            return app('wechat_pay')->success();
        }

        // 将订单标记为已支付
        $order->update([
            'paid_at'        => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no'     => $data->transaction_id,
        ]);

        return app('wechat_pay')->success();
    }
}
