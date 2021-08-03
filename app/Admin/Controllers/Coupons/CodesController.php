<?php

namespace App\Admin\Controllers\Coupons;

use App\Models\Coupon\Code;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class CodesController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Code(), function (Grid $grid) {
            $grid->model()->orderBy('updated_at', 'desc');

            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('code');
            $grid->column('description');
            $grid->column('usage')->display(function ($value) {
                return "{$this->used} / {$this->total}";
            });
            $grid->column('start_at');
            $grid->column('end_at');
            $grid->column('status')->switch();
        
            $grid->toolsWithOutline(false);
            $grid->disableViewButton();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();

                $filter->equal('id');
            });
        });
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Code(), function (Form $form) {
            $form->display('id');
            $form->text('name')->rules('required');
            $form->text('code')->rules(function($form) {
                if ($id = $form->model()->id) {
                    return 'required|unique:coupon_codes,code,'.$id.',id';
                } else {
                    return 'required|unique:coupon_codes';
                }
            })->default(Code::findAvailableCode());
            $form->radio('type')->options(Code::$typeMap)->rules('required')->default(Code::TYPE_FIXED);
            $form->text('value')->rules(function ($form) {
                if (request()->input('type') === Code::TYPE_PERCENT) {
                    return 'required|numeric|between:1,99';
                }
                
                return 'required|numeric|min:0.01';
            });
            $form->text('total')->rules('required|numeric|min:1');
            $form->text('min_amount')->rules('required|numeric|min:1');
            $form->datetime('start_at');
            $form->datetime('end_at');
            $form->radio('status')->options([
                '1' => '是',
                '0' => '否',
            ]);

            $form->saving(function (Form $form) {
                if (!$form->code) {
                    $form->code = Code::findAvailableCode();
                }
            });

            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
        });
    }
}
