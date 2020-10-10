<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Order;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class OrderController extends AdminController
{
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
    protected function detail($id)
    {
        return Show::make($id, new Order(), function (Show $show) {
            $show->field('id');
            $show->field('no');
            $show->field('user_id');
            $show->field('address');
            $show->field('total_amount');
            $show->field('remark');
            $show->field('paid_at');
            $show->field('payment_method');
            $show->field('payment_no');
            $show->field('refund_status');
            $show->field('refund_no');
            $show->field('closed');
            $show->field('reviewed');
            $show->field('ship_status');
            $show->field('ship_data');
            $show->field('extra');
            $show->field('created_at');
            $show->field('updated_at');
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Order(), function (Form $form) {
            $form->display('id');
            $form->text('no');
            $form->text('user_id');
            $form->text('address');
            $form->text('total_amount');
            $form->text('remark');
            $form->text('paid_at');
            $form->text('payment_method');
            $form->text('payment_no');
            $form->text('refund_status');
            $form->text('refund_no');
            $form->text('closed');
            $form->text('reviewed');
            $form->text('ship_status');
            $form->text('ship_data');
            $form->text('extra');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
