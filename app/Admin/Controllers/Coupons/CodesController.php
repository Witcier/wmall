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
     * Make a show builder.
     *
     * @param mixed $id
     *
     * @return Show
     */
    protected function detail($id)
    {
        return Show::make($id, new Code(), function (Show $show) {
            $show->field('id');
            $show->field('name');
            $show->field('code');
            $show->field('type');
            $show->field('value');
            $show->field('total');
            $show->field('used');
            $show->field('min_amount');
            $show->field('start_at');
            $show->field('end_at');
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
        return Form::make(new Code(), function (Form $form) {
            $form->display('id');
            $form->text('name');
            $form->text('code');
            $form->text('type');
            $form->text('value');
            $form->text('total');
            $form->text('used');
            $form->text('min_amount');
            $form->text('start_at');
            $form->text('end_at');
            $form->text('status');
        
            $form->display('created_at');
            $form->display('updated_at');
        });
    }
}
