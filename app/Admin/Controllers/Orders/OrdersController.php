<?php

namespace App\Admin\Controllers\Orders;

use App\Models\Order\Order;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;

class OrdersController extends AdminController
{
    protected function grid()
    {
        return Grid::make(Order::with(['user']), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('user.name', '买家');
            $grid->column('total_amount');
            $grid->column('no');
            $grid->column('closed')->bool([
                '0' => true,
                '1' => false,
            ]);
            $grid->column('paid')->bool();
            $grid->column('payment_method')->using(Order::$paymentMethodMap)
                ->label([
                    '1' => 'blue',
                    '2' => 'green',
                ]);
            $grid->column('refund_status')->using(Order::$refundStatusMap)
                ->label([
                    '0' => 'green',
                    '1' => 'red',
                    '2' => 'yellow',
                    '3' => 'green',
                    '4' => 'red',
                ]);
            $grid->column('reviewed')->bool();
            $grid->column('ship_status')->using(Order::$shipStatusMap)
                ->label([
                    '0' => 'yellow',
                    '1' => 'blue',
                    '2' => 'green',
                ]);
            $grid->column('created_at');
        
            $grid->toolsWithOutline(false);
            $grid->disableCreateButton();
            $grid->disableDeleteButton();
            $grid->disableEditButton();
            $grid->disableBatchActions();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();

                $filter->equal('id');
            });
        });
    }

    public function show($id, Content $content)
    {
        return $content
            ->header('订单详情')
            ->body(view('admin.orders.show', [
                'order' => Order::find($id),
            ]));
    }

    public function ship(Order $order, Request $request)
    {
        if (!$order->paid) {
            admin_error('标题', '该订单未支付');
        }

        if ($order->ship_status !== Order::SHIP_STATUS_PENDING) {
            admin_error('标题', '订单已经发货');
        }

        $data = $this->validate($request, [
            'express_company' => "required",
            'express_no'      => "required",
        ],
        [],
        [
            'express_company' => '物流公司',
            'express_no'      => '物流单号',
        ]);

        $order->update([
            'ship_status' => Order::SHIP_STATUS_DELIVERED,
            'ship_data'   => $data,
        ]);

        return redirect()->back();
    }
}
