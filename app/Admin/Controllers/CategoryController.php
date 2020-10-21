<?php

namespace App\Admin\Controllers;

use App\Admin\Repositories\Category;
use App\Models\Category as AppCategory;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;

class CategoryController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new Category(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('parent_id');
            $grid->column('is_directory')->display(function ($value) {
                return $value ? '是' : '否';
            });
            $grid->column('level');
            $grid->column('path');
            $grid->column('created_at')->sortable();

            $grid->actions(function ($actions) {
                $actions->disableView();
            });
        
            $grid->filter(function (Grid\Filter $filter) {
                $filter->equal('id');
        
            });
        });
    }

    public function edit($id, Content $content)
    {
        return $content
            ->title($this->title())
            ->description($this->description['edit'] ?? trans('admin.edit'))
            ->body($this->form(true)->edit($id));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        return Form::make(new Category('parent'), function (Form $form) {
            $form->text('name')->rules('required');

            // 如果是编辑的情况
            if ($form->isEditing()) {
               // 不允许用户修改 ‘是否目录’ 和 ‘父类目’ 字段的值
               // 用 display() 方法来展示值，with() 方法接受一个匿名函数，会把字段值传给匿名函数并把返回值展示出来
               $form->display('is_directory')->with(function ($value) {
                   return $value ? '是' : '否';
               });
               // 支持用符号 。 来展示关联关系的字段
               $form->display('parent.name','父类目');
            } else {
                // 定义一个名为 ‘是否目录’ 的单选框
                $form->radio('is_directory')->options(['1' => '是', '0' => '否'])->default('0')->rules('required');

                // 定义一个名为父类目的下拉框
                $form->select('parent_id','父类目')->ajax('/api/categories');
            }
        });
    }

    public function apiIndex(Request $request)
    {
        // 用户输入的值通过 q 参数获取
        $search = $request->input('q');
        $reslut = AppCategory::query()
            ->where('is_directory',true)
            ->where('name', 'like', '%'.$search.'%')
            ->paginate();

        // 把查询出来的结果重新组成 DCAT-ADMIN 需要的格式
        $reslut->setCollection($reslut->getCollection()->map(function (AppCategory $category) {
            return ['id' => $category->id,'text' => $category->full_name];
        }));

        return $reslut;
    }
}
