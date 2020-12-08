<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use App\Models\Order;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
             'payment_method' => 'alipay',
             'payment_no' => $data->trade_no,
         ]);

         $this->afterPaid($order);

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

        $this->afterPaid($order);

        return app('wechat_pay')->success();
    }

    public function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function wechatRefundNotify(Request $request)
    {
        // 给微信的失败响应
        $failXml = '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[FAIL]]></return_msg></xml>';
        $data = app('wechat_pay')->verify(null,true);

        // 没有找到相应的订单，原则上不可能发生，保证代码健壮性
        if (!$order = Order::where('no',$data['out_trade_no'])->first()) {
            return $failXml;
        }

        if ($data['refund_status'] === 'SUCCESS') {
            // 退款成功，将订单退款状态改为退款成功
            $order->update([
                'refund_status' => Order::REFUND_STATUS_SUCCESS,
            ]);
        } else {
            // 退款失败，将订单退款状态改为退款失败
            $extra = $order->extra;
            $extra['refund_failed_code'] = $data['refund_status'];
            $order->update([
                'refund_status' => Order::REFUND_STATUS_FAILED,
                'extra' => $extra,
            ]);
        }

        return app('wechat_pay')->success();
    }

    public function payByInstallment(Order $order, Request $request)
    {
        // 判断订单是否属于当前用户
        $this->authorize('own', $order);

        // 判断订单是否支付或者已被关闭
        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态异常');
        }

        // 订单不满足最低分期要求
        if ($order->total_amount < config('app.min_installment_amount')) {
            throw new InvalidRequestException('订单金额低于最低分期金额'.config('app.min_installment_amount'));
        }

        // 判断订单是否已经创建分期付款
        if ($installment = Installment::where('order_id', $order->id)->first()) {
            throw new InvalidRequestException('该订单已经创建分期付款了');
        }

        // 校验用户的提交还款月数，数值必须是设置好利率的期数
        $this->validate($request, [
            'count' => ['required', Rule::in(array_keys(config('app.installment_fee_rate')))]
        ]);

        // 删除同一笔商品订单发起过其他的状态是未支付的分期付款，避免一笔订单有多个分期
        Installment::query()
            ->where('order_id', $order->id)
            ->where('status', Installment::STATUS_PENDING)
            ->delete();

        $count = $request->input('count');

        // 创建一个新的分期付款对象
        $installment = new Installment([
            // 总金额为订单商品的总金额
            'total_amount' => $order->total_amount,
            // 分期期数
            'count' => $count,
            // 从配置文件获取相应期数的利率
            'fee_rate' => config('app.installment_fee_rate')[$count],
            // 从配置获取逾期的利率
            'fine_rate' => config('app.installment_fine_rate'),
        ]);

        $installment->user()->associate($request->user());
        $installment->order()->associate($order);
        $installment->save();

        // 第一期的还款日期
        $dueDate = Carbon::tomorrow();

        // 计算每一期的本金
        $base = big_number($order->total_amount)->divide($count)->getValue();
        // 计算每一期的利息
        $fee = big_number($base)->multiply($installment->fee_rate)->divide(100)->getValue();

        // 根据用户选择的还款期数，创建相应的还款计划
        for ($i = 0; $i  < $count; $i++) {
            // 最后一期的本金需要用总本金减去前面几期的本金
            if ($i === $count-1) {
                $base = big_number($order->total_amount)->subtract(big_number($base)->multiply($count - 1));
            }

            $installment->items()->create([
                'sequence' => $i,
                'base' => $base,
                'fee' => $fee,
                'due_date' => $dueDate,
            ]);

            // 还款截止日期加 30 天
            $dueDate = $dueDate->copy()->addDays(30);
        }

        return $installment;

    }
}
