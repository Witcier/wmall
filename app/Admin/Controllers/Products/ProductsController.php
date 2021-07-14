<?php

namespace App\Admin\Controllers\Products;

use App\Models\Product\Product;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

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
            $grid->column('description');
            $grid->column('image')->image('', 80, 80);
            $grid->column('on_sale')->switch();
            $grid->column('rating');
            $grid->column('sold_count');
            $grid->column('review_count');
            $grid->column('price');
        
            $grid->toolsWithOutline(false);
            $grid->disableViewButton();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();

                $filter->like('title')->width(4);
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
        return Form::make(Product::with('skus'), function (Form $form) {
            $form->text('title')->rules('required|string');
            $form->textarea('description')->rules('required');
            $form->image('image')->rules('required|image')->uniqueName()->autoUpload();
            $form->radio('on_sale')->options(['1' => '是', '0' => '否'])->default(0);

            $form->hasMany('skus', function (Form\NestedForm $form) {
                $form->text('title')->rules('required|string');
                $form->text('description')->rules('required');
                $form->text('price')->rules('required|numeric|min:0.01');
                $form->text('stock')->rules('required|numeric|min:0');
            });

            // 定义事件回调，当模型即将保存时会触发这个回调
            $form->saving(function (Form $form) {
                $form->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
            });

            // 禁用工具
            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
        });
    }
}
