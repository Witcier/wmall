<?php

namespace App\Http\Controllers\Order;

use App\Events\OrderReviewed;
use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderRequest;
use App\Http\Requests\Order\ReviewedRequest;
use App\Jobs\CloseOrder;
use App\Models\Order\Order;
use App\Models\Product\Sku;
use App\Models\User\Address;
use App\Services\CartService;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $orders = Order::query()
            ->with(['items.product', 'items.productSku'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate();

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    public function store(OrderRequest $request, OrderService $orderService)
    {
        $address = Address::find($request->input('address_id'));

        return $orderService->store($address, $request->input('remark'), $request->input('items'));
    }

    public function show(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        return view('orders.show', [
            'order' => $order->load(['items.product', 'items.productSku']),
        ]);
    }

    public function received(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED) {
            throw new InvalidRequestException('订单状态不正确');
        }

        $order->update([
            'ship_status' => Order::SHIP_STATUS_RECEIVED,
        ]);

        return $order;
    }

    public function review(Order $order)
    {
        $this->authorize('own', $order);

        if (!$order->paid) {
            throw new InvalidRequestException('订单未支付');
        }

        return view('orders.review', [
            'order' => $order->load(['items.product', 'items.productSku']),
        ]);
    }

    public function reviewed(Order $order, ReviewedRequest $request)
    {
        $this->authorize('own', $order);

        if (!$order->paid) {
            throw new InvalidRequestException('订单未支付');
        }

        if ($order->reviewed) {
            throw new InvalidRequestException('订单已评价');
        }

        $reviews = $request->input('reviews');

        \DB::transaction(function () use ($order, $reviews) {
            foreach ($reviews as $review) {
                $item = $order->items()->find($review['id']);

                $item->update([
                    'rating' => $review['rating'],
                    'review' => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }

            $order->update([
                'reviewed' => true,
            ]);
        });

        event(new OrderReviewed($order));

        return redirect()->back();
    }
}
