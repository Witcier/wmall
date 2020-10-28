<?php

namespace App\Admin\Controllers;

use App\Models\Category;
use App\Models\CrowdfundingProduct;
use App\Admin\Repositories\Product;
use App\Models\Product as AppProduct;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;

class CrowdfundingProductController extends CommonProductController
{
    public function getProductType()
    {
        return AppProduct::TYPE_CROWDFUNDING;
    }

    protected function customGrid(\Dcat\Admin\Grid $grid)
    {
        $grid->column('id')->sortable();
        $grid->column('title');
        $grid->image()->image('',80,80);
        $grid->column('status')->switch();
        $grid->column('price');
        // 展示众筹相关字段
        $grid->column('crowdfunding.total_amount','已筹金额');
        $grid->column('crowdfunding.target_amount','目标金额');
        $grid->column('crowdfunding.status','状态')->display(function ($value) {
            return CrowdfundingProduct::$statusMap[$value];
        });
        $grid->column('crowdfunding.end_at','结束时间');
    }

    protected function customForm(\Dcat\Admin\Form $form)
    {
        $form->text('crowdfunding.target_amount', '众筹目标金额')->rules('required|numeric|min:0.01');
        $form->datetime('crowdfunding.end_at', '众筹结束时间')->rules('required|date');
    }
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    // protected function grid()
    // {
    //     return Grid::make(new Product('crowdfunding'), function (Grid $grid) {
    //         // 只显示 type 为众筹类型的商品
    //         $grid->model()->where('type', AppProduct::TYPE_CROWDFUNDING);
    //         $grid->column('id')->sortable();
    //         $grid->column('title');
    //         $grid->image()->image('',80,80);
    //         $grid->column('status')->switch();
    //         $grid->column('price');
    //         // 展示众筹相关字段
    //         $grid->column('crowdfunding.target_amount','目标金额');
    //         $grid->column('crowdfunding.total_amount','当前金额');
    //         $grid->column('crowdfunding.status','状态')->display(function ($value) {
    //             return CrowdfundingProduct::$statusMap[$value];
    //         });
    //         $grid->column('crowdfunding.end_at','结束时间');

    //         // 操作
    //         $grid->actions(function ($actions) {
    //             $actions->disableView();
    //             $actions->disableDelete();
    //         });

    //         // 工具
    //         $grid->tools(function ($tools) {
    //             $tools->batch(function ($batch) {
    //                 $batch->disableDelete();
    //             });
    //         });

    //         $grid->filter(function (Grid\Filter $filter) {
    //             $filter->equal('id');
    //         });
    //     });
    // }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    // protected function form()
    // {
    //     return Form::make(new Product('skus'), function (Form $form) {
    //         // 添加 type 隐藏字段， 默认值为众筹类型
    //         $form->hidden('type')->value(AppProduct::TYPE_CROWDFUNDING);

    //         $form->text('title')->rules('required');
    //         $form->select('category_id','分类')->options(function ($id) {
    //             $category = Category::find($id);
    //             if ($category) {
    //                 return [$category->id => $category->full_name];
    //             }
    //         })->ajax('api/categories?is_directory=0');
    //         $form->image('image')->rules('required|image');
    //         $form->editor('description')->rules('required');
    //         $form->radio('status')->options(['1' => '是', '0' => '否'])->default(0);
    //         $form->text('crowdfunding.target_amount', '众筹目标金额')->rules('required|numeric|min:0.01');
    //         $form->datetime('crowdfunding.end_at', '众筹结束时间')->rules('required|date');
    //         $form->hasMany('skus', '商品Sku', function (Form\NestedForm $form) {
    //             $form->text('title', 'Sku名称')->rules('required');
    //             $form->text('description', 'Sku描述')->rules('required');
    //             $form->text('price','Sku价格')->rules('required|numeric|min:0.01');
    //             $form->text('stock', '库存')->rules('required|integer|min:0');
    //         });

    //         $form->saving(function (Form $form) {
    //             $form->price = collect($form->input('skus'))->where(Form::REMOVE_FLAG_NAME,0)->min('price') ?: 0; 
    //         });
    //     });
    // }
}
