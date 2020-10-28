<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Product;
use App\Models\Category;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Grid;
use App\Models\Product as AppProduct;
use Dcat\Admin\Form;

abstract class CommonProductController extends AdminController
{
    // 定义一个抽象方法， 返回当前管理的商品类型
    abstract public function getProductType();

    // 定义一个抽象方法，各个类型的控制器将实现本方法来定义列表应该展示哪些字段
    abstract protected function customGrid(Grid $grid);

    // 定义一个抽象方法，各个类型的控制器将实现本方法来定义表单应该有哪些额外的字段
    abstract protected function customForm(Form $form);

    protected function grid()
    {
        return Grid::make(new Product('crowdfunding'), function (Grid $grid) {
            // 根据类型筛选出商品
            $grid->model()->where('type', $this->getProductType())->orderBy('updated_at', 'desc');
            // 调用自定义方法
            $this->customGrid($grid);

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

    protected function form()
    {
        return Form::make(new Product('skus'), function (Form $form) {

            // 隐藏字段 type
            $form->hidden('type')->value($this->getProductType());

            $form->text('title')->rules('required');
            $form->image('image')->rules('required|image');

            // 添加一个分类字段， 与之前的分类管理类似， 使用ajax的方式来添加搜索
            $form->select('category_id','分类')->options(function ($id) {
                $category = Category::find($id);
                if ($category) {
                    return [$category->id => $category->full_name];
                }
            })->ajax('api/categories?is_directory=0');

            $form->editor('description')->rules('required');
            $form->radio('status')->options(['1' => '是','0' => '否'])->default(0);

            // 调用自定义方法
            $this->customForm($form);

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