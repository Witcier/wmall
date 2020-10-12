<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Product;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class ProductsController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Product(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('title');
            $grid->image()->image('',80,80);
            $grid->column('status')->display(function ($value) {
                return $value ? '是' : '否';
            })->label([
                1 => 'success',
                0 => 'danger',
            ]);
            $grid->column('rating')->sortable();
            $grid->column('sold_count')->sortable();
            $grid->column('review_count')->sortable();
            $grid->column('price')->sortable();
            $grid->column('created_at');
            $grid->column('updated_at')->sortable();
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
            });

            $grid->actions(function ($actions) {
                $actions->disableView();
            });

            $grid->tools(function ($tools) {
                // 禁用批量删除按钮
                $tools->batch(function ($batch) {
                    $batch->disableDelete();
                });
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
        return Show::make($id, new Product(), function (Show $show) {
            $show->field('id');
            $show->field('title');
            $show->field('description');
            $show->field('image');
            $show->field('status');
            $show->field('rating');
            $show->field('sold_count');
            $show->field('review_count');
            $show->field('price');
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
        return Form::make(new Product('skus'), function (Form $form) {
            $form->display('id');
            $form->text('title')->rules('required');
            $form->image('image')->rules('required|image');
            $form->editor('description')->rules('required');
            $form->radio('status')->options(['1' => '是','0' => '否'])->default(0);
            $form->hasMany('skus','商品规格表',function (Form\NestedForm $form) {
                $form->text('title','规格标题')->rules('required');
                $form->text('description')->rules('required');
                $form->text('price')->rules('required|numeric|min:0.01');
                $form->text('stock','库存')->rules('required|integer|min:0');
            });

            $form->saving(function (Form $form) {
                $form->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME,0)->min('price') ?: 0; 
            });

            $form->disableViewButton();
        });
    }
}
