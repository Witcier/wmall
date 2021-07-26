<?php

namespace App\Http\Controllers\Order;

use App\Exceptions\InvalidRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\OrderRequest;
use App\Jobs\CloseOrder;
use App\Models\Order\Order;
use App\Models\Product\Sku;
use App\Models\User\Address;
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

    public function store(OrderRequest $request)
    {
        $user = $request->user();

        $order = \DB::transaction(function () use ($user, $request) {
            $address = Address::find($request->input('address_id'));

            $address->update([
                'last_used_at' => Carbon::now(),
            ]);
            $addressData = [ // 将地址信息放入订单中
                'address' => $address->full_address,
                'zip' => $address->zip,
                'contact_name' => $address->contact_name,
                'contact_phone' => $address->contact_phone,
            ];
            $order = new Order([
                'address' => $addressData,
                'remark' => $request->input('remark'),
                'total_amount' => 0,
            ]);

            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;
            $items = $request->input('items');
            foreach ($items as $data) {
                $sku = Sku::find($data['sku_id']);

                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();

                $totalAmount += $sku->price * $data['amount'];

                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('商品库存不足');
                }
            }

            $order->update([
                'total_amount' => $totalAmount,
            ]);

            $skuIds = collect($items)->pluck('sku_id');
            $user->cartItems()->whereIn('product_sku_id', $skuIds)->delete();

            return $order;
        });

        $this->dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;
    }
}
