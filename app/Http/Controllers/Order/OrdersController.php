<?php

namespace App\Http\Controllers\Order;

use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderRequest;
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
}
