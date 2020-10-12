<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Order;
use App\Exceptions\InvalidRequestException;
use App\Http\Requests\Request;
use App\Models\Order as AppOrder;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Illuminate\Foundation\Validation\ValidatesRequests;

class OrderController extends AdminController
{
    use ValidatesRequests;
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Order('user'), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('no');
            $grid->column('user.name','买家');
            $grid->column('total_amount')->sortable();
            $grid->column('payment_method')->display( function ($value) {
                return $value === 'alipay' ? '支付宝支付' : ($value === 'alipay' ? '微信支付' : '未支付');
            })->label('success');
            $grid->column('closed')->display(function ($value) {
                return $value ? '是' : '否';
            })->label([
                1 => 'danger',
                0 => 'default',
            ]);
            $grid->column('reviewed');
            $grid->column('ship_status')->display( function ($value) {
                return Order::$shipStatusMap[$value];
            });
            $grid->column('refund_status')->display( function ($value) {
                return Order::$refundStatusMap[$value];
            });
            $grid->column('remark');
            $grid->column('paid_at');
            $grid->column('created_at')->sortable();

            // 操作
            $grid->actions(function ($actions) {
                $actions->disableDelete();
                $actions->disableEdit();
            });
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
                $filter->like('user.name','买家');
            });

            $grid->toolsWithOutline(false);
            
            $grid->filter(function (Grid\Filter $filter) {
                // 更改为 panel 布局
                $filter->panel();
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

        $order = AppOrder::find($id);
        $data = [
            'order' => $order
        ];
        return $content
            ->title('订单')
            ->description('详情')
            ->body($this->_detail($data));
    }

    private function _detail($data)
    {
        return view('admin/orders/show', $data);
    }

    public function ship(AppOrder $order, Request $request)
    {
        // 判断当前订单是否已经支付
        if (!$order->paid_at) {
            throw new InvalidRequestException('该订单未付款');
        }
        // 判断订单是否已经发货
        if ($order->ship_status !== Order::SHIP_STATUS_PENDING) {
            throw new InvalidRequestException('该订单已发货');
        }

        $data = $this->validate($request, [
            'express_company' => ['required'],
            'express_no'      => ['required'],
        ],[],[
            'express_company' => '物流公司',
            'express_no'      => '物流单号',
        ]);

        // 将订单发货状态改为发货
        $order->update([
            'ship_status' => Order::SHIP_STATUS_DELIVERED,
            'ship_data'   => $data,
        ]);

        return redirect()->back();
    }
}
