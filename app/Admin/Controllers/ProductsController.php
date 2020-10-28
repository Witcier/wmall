<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Product;
use App\Models\Category;
use App\Models\Product as AppProduct;
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
            $grid->model()->orderBy('updated_at', 'desc');
            // 使用 with 来预加载商品类目数据，减少 SQL 查询
            $grid->model()->where('type', AppProduct::TYPE_NORMAL)->with(['category']);
            
            $grid->column('id')->sortable();
            $grid->column('title')->filter(
                Grid\Column\Filter\Like::make()
            );
            $grid->image()->image('',80,80);
            $grid->column('category.name','分类');
            $grid->column('status')->switch();
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
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Product('skus'), function (Form $form) {

            // 隐藏字段 type
            $form->hidden('type')->value(AppProduct::TYPE_NORMAL);
            
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
