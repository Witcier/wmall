<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use App\Exceptions\InvalidRequestException;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InstallmentsController extends Controller
{
    public function index(Request $request)
    {
        $installments = Installment::query()
            ->where('user_id',$request->user()->id)
            ->paginate(10);

        return view('installments.index',['installments' => $installments]);
    }

    public function show(Installment $installment)
    {
        $this->authorize('own',$installment);

        $items = $installment->items()->orderBy('sequence')->get();

        return view('installments.show',[
            'installment' => $installment,
            'items' => $items,
            // 下一个未完成还款的计划
            'nextItem' => $items->where('paid_at',null)->first(),
        ]);
    }

    public function payByAlipay(Installment $installment)
    {
        if ($installment->order->close) {
            throw new InvalidRequestException('对应的商品订单已关闭');
        }

        if ($installment->status === Installment::STATUS_FINISHED) {
            throw new InvalidRequestException('该分期订单已经结清');
        }

          // 获取当前分期付款最近的一个未支付的还款计划
          if (!$nextItem = $installment->items()->whereNull('paid_at')->orderBy('sequence')->first()) {
            // 如果没有未支付的还款，原则上不可能，因为如果分期已结清则在上一个判断就退出了
            throw new InvalidRequestException('该分期订单已结清');
        }

        // 调用支付宝的网页支付
        return app('alipay')->web([
            // 支付订单号使用分期流水号+还款计划号
            'out_trade_no' => $installment->no.'_'.$nextItem->sequence,
            'total_amount' => $nextItem->total,
            'subject' => '支付 Witcier Mall 的分期订单'.$installment->no,
            // 这里的 notify_url 和 return_url 可以覆盖掉在 AppServiceProvider 设置的回调地址
            'notify_url' => ngrok_url('installments.alipay.notify'),
            'return_url' => route('installments.alipay.return'),
        ]);
    }

    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('index.error',['msg' => '数据不正确']);
        }

        return view('index.success',['msg' => '付款成功']);
    }

    public function alipayNotify()
    {
        // 校验支付宝回调参数是否正确
        $data = app('alipay')->verify();
        // 如果订单状态不是成功或者结束，则不走后续的逻辑
        if (!in_array($data->trade_status,['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        // 拉起支付时使用的支付订单号是由分期流水号 + 还款计划编号组成的
        // 因此可以通过支付订单号来还原出这笔还款是哪个分期付款的哪个还款计划
        list($no, $sequence) = explode('_', $data->out_trade_no);

        // 根据分期流水号查询对应的分期记录
        if (!$installment = Installment::where('no', $no)->first()) {
            return 'fail';
        }

        // 根据还款计划编号查询对应的还款计划
        if (!$item = $installment->items()->where('sequence', $sequence)->first()) {
            return 'fail';
        }

        // 判断该计划是否已支付
        if ($item->paid_at) {
            return app('alipay')->success();
        }

        // 开启事务保持数据一致性
        \DB::transaction(function () use ($data, $no, $installment, $item) {
            // 更新相应的计划
            $item->update([
                'paid_at' => Carbon::now(),
                'payment_method' => 'alipay',
                'payment_no' => $data->trade_no,
            ]);

            // 如果是第一笔还款
            if ($item->sequence === 0) {
                // 将分期付款的状态改为还款中
                $installment->update(['status' => Installment::STATUS_REPAYING]);

                // 将分期付款对应的商品订单改为已支付
                $installment->order->update([
                    'paid_at' => Carbon::now(),
                    'payment_method' => 'installment',
                    'payment_no' => $no,

                ]);

                // 触发商品订单已支付的事件
                event(new OrderPaid($installment->order));
            }

            // 如果是最后一笔还款
            if ($item->sequence === $installment->count-1) {
                // 将分期付款状态改为已结清
                $installment->update(['status', Installment::STATUS_FINISHED]);
            }
        });

        return app('alipay')->success();
    }
}
