<?php

namespace App\Admin\Controllers;

use App\Models\CouponCode;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class CouponCodeController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new CouponCode(), function (Grid $grid) {
            $grid->column('name');
            $grid->column('code');
            $grid->column('description','优惠卷');
            $grid->column('useage','已使用/总量')->display( function ($value) {
                return "{$this->used} / {$this->total}";
            });
            // $grid->column('status')->display( function ($value) {
            //     return $value ? '是' : '否';
            // })->label([
            //     1 => 'primary',
            //     0 => 'default',
            // ]);
            $grid->column('status')->switch();
            $grid->column('start_time');
            $grid->column('end_time');
            
            $grid->actions(function ($actions) {
                $actions->disableView();
            });

            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
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
        return Show::make($id, new CouponCode(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('code');
            $show->field('type');
            $show->field('value');
            $show->field('total');
            $show->field('used');
            $show->field('min_amount');
            $show->field('start_time');
            $show->field('end_time');
            $show->field('status');
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
        return Form::make(new CouponCode(), function (Form $form) {
            $form->text('name')->rules('required');
            $form->text('code')->rules('nullable|unique:coupon_codes');
            $form->radio('type')->options(CouponCode::$typeMap)->rules('required')->default(CouponCode::TYPE_FIXED);
            $form->text('value')->rules(function ($form) {
                if (request()->input('type') === CouponCode::TYPE_PERCENT) {
                    // 如果选择了百分比折扣类型，那么折扣范围只能是 1 ~ 99
                    return 'required|numeric|between:1,99';
                } else {
                    // 否则只要大等于 0.01 即可
                    return 'required|numeric|min:0.01';
                }
            });
            $form->text('total')->rules('required|numeric|min:0');
            $form->text('min_amount')->rules('required|numeric|min:0');
            $form->datetime('start_time');
            $form->datetime('end_time');
            $form->radio('status')->options(['1' => '是','0' => '否'])->default(0);
            
            $form->saving(function (Form $form) {
                if (!$form->code) {
                   $form->code = CouponCode::findAvailableCode();
                }
            });
        });
    }
}
