<?php

namespace App\Admin\Controllers\Products;

use App\Jobs\SyncOneProductToES;
use App\Models\Product\Category;
use App\Models\Product\Product;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Http\Controllers\AdminController;

abstract class CommonController extends AdminController
{
    abstract public function getProductType();

    protected function grid()
    {
        return Grid::make(new Product(), function (Grid $grid) {
            $grid->model()->where('type', $this->getProductType())->with(['category'])->orderBy('updated_at', 'desc');
            
            $this->customGrid($grid);
        
            $grid->toolsWithOutline(false);
            $grid->disableViewButton();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();

                $filter->like('title')->width(4);
            });
        });
    }

    abstract protected function customGrid(Grid $grid);

    protected function form()
    {
        return Form::make(Product::with('skus', 'crowdfunding', 'properties'), function (Form $form) {
            $form->hidden('type')->value($this->getProductType());

            $form->text('title')->rules('required|string');
            $form->text('long_title')->rules('required|string');
            $form->select('product_category_id')
                ->options(Category::selectOptions())
                ->rules('required');
            $form->editor('description')->rules('required');
            $form->image('image')->rules('required|image')->uniqueName()->autoUpload();
            $form->radio('on_sale')->options(['1' => '是', '0' => '否'])->default(0);

            $this->customForm($form);

            $form->hasMany('skus', function (Form\NestedForm $form) {
                $form->text('title')->rules('required|string');
                $form->text('description')->rules('required');
                $form->text('price')->rules('required|numeric|min:0.01');
                $form->text('stock')->rules('required|numeric|min:0');
            });

            $form->hasMany('properties', function (Form\NestedForm $form) {
                $form->text('name')->rules('required');
                $form->text('value')->rules('required');
            });

            // 定义事件回调，当模型即将保存时会触发这个回调
            $form->saving(function (Form $form) {
                $form->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME, 0)->min('price') ?: 0;
            });

            $form->saved(function (Form $form) {
                $product = $form->model();

                SyncOneProductToES::dispatch($product);
            });

            // 禁用工具
            $form->disableViewButton();
            $form->disableViewCheck();
            $form->disableEditingCheck();
            $form->disableCreatingCheck();
        });
    }

    abstract protected function customForm(Form $form);
}