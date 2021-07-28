<?php

namespace App\Admin\Controllers\Orders;

use App\Models\Order\Order;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;

class OrdersController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
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

    /**
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    public function show($id, Content $content)
    {
        return $content
            ->header('订单详情')
            ->body(view('admin.orders.show', [
                'order' => Order::find($id),
            ]));
    }
}
