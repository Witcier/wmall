<?php

namespace App\Http\Controllers;

use App\Events\OrderReviewed;
use App\Exceptions\CouponCodeUnavailableException;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\ApplyRefundRequest;
use App\Http\Requests\CrowdFundingOrderRequest;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\SeckillOrderRequest;
use App\Http\Requests\SendReviewRequest;
use App\Jobs\CloseOrder;
use App\Models\CouponCode;
use App\Models\ProductSku;
use App\Models\UserAddress;
use App\Models\Order;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            // 使用 with 方法预加载，避免N + 1问题
            ->with(['items.product','items.productSku'])
            ->where('user_id',$request->user()->id)
            ->orderBy('created_at','desc')
            ->paginate();

            return view('orders.index',['orders' => $orders]);
    }

    public function store(OrderRequest $request,OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        $coupon = null;

        if ($code = $request->input('coupon_code')) {
           $coupon = CouponCode::where('code', $code)->first();
           if (!$coupon) {
               throw new CouponCodeUnavailableException('优惠卷不存在');
           }
        }

        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'),$coupon);
    }

    public function show(Order $order, Request $request)
    {
        $this->authorize('own',$order);
        return view('orders.show',['order' => $order->load(['items.productSku','items.product'])]);
    }

    public function received(Order $order, Request $request)
    {
        // 校验权限
        $this->authorize('own',$order);

        // 判断订单是否已经发货
        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('订单未发货');
        }

        //更新订单发货状态
        $order->update(['ship_status' => Order::SHIP_STATUS_RECEIVED]);

        return $order;
    }

    public function review(Order $order)
    {
        // 校验权限
        $this->authorize('own',$order);

        // 判断订单是否已支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可评价');
        }

        // 判断是否已经收货
        if ($order->ship_status !== Order::SHIP_STATUS_RECEIVED) {
            throw new InvalidRequestException('该订单未收货，不可评价');
        }

        // 使用 load 方法加载关联数据，避免 N + 1 性能问题
        return view('orders.review',['order' => $order->load(['items.productSku','items.product'])]);
    }

    public function sendReview(Order $order, SendReviewRequest $request, OrderService $orderService)
    {
        // 校验权限
        $this->authorize('own',$order);

        // 判断是否支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可评价');
        }

        // 判断是否已经收货
        if ($order->ship_status !== Order::SHIP_STATUS_RECEIVED) {
            throw new InvalidRequestException('该订单未收货，不可评价');
        }

        // 判断订单是否已评价
        if ($order->reviewed) {
            throw new InvalidRequestException('该订单已评价，不可重复提交');
        }

        $reviews = $request->input('reviews');

        $orderService->sendReview($order, $reviews);

        return redirect()->back();
    }

    public function applyRefund(Order $order, ApplyRefundRequest $request, OrderService $orderService)
    {
        // 校验权限
        $this->authorize('own',$order);

        // 判断订单是否已经付款
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未支付，不可退款');
        }

        // 众筹商品订单， 不允许退款
        if ($order->type === Order::TYPE_CROWDFUNDING) {
            throw new InvalidRequestException('众筹商品订单不支持退款');
        }

        // 判断订单是否已经申请退款了
        if ($order->refund_status !== Order::REFUND_STATUS_PENDING) {
            throw new InvalidRequestException('该订单已经申请退款了，请勿重复申请');
        }

        $reason = $request->input('reason');

        return $orderService->applyRefund($order, $reason);
    }

    public function crowdfunding(CrowdFundingOrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        $sku = ProductSku::find($request->input('sku_id'));
        $amount = $request->input('amount');

        return $orderService->crowdfunding($user, $address, $sku, $amount);
    }

    public function seckill(SeckillOrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $sku = ProductSku::find($request->input('sku_id'));

        return $orderService->seckill($user, $request->input('address'), $sku);
    }
}
