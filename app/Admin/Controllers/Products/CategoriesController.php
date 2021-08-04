<?php

namespace App\Admin\Controllers\Products;

use App\Models\Product\Category;
use Dcat\Admin\Form;
use Dcat\Admin\Http\Controllers\AdminController;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Tree;

class CategoriesController extends AdminController
{
    protected $title = '商品-分类';

    public function index(Content $content)
    {
        return $content->header('导航-分类')
            ->description(trans('admin.list'))
            ->body($this->treeView());
    }

    protected function treeView()
    {
        return new Tree(new Category(), function (Tree $tree) {
            $tree->disableCreateButton();
        });
    }

    /**
     * 编辑分类
     * @param Content $content
     * @param $id 分类ID
     * @return Content
     */
    public function edit($id, Content $content)
    {
        return $content->header('编辑分类')
            ->body($this->form()->edit($id));
    }

    protected function form()
    {
        return Form::make(new Category(), function (Form $form) {
            $form->select('parent_id')
                ->options(Category::selectOptions())
                ->rules('required');
            $form->text('name')
                ->rules('required|max:50')
                ->placeholder('不得超过50个字符');

            $form->tools(function (Form\Tools $tools) {
                $tools->disableDelete();
                $tools->disableView();
            });

            $form->footer(function ($footer) {
                $footer->disableViewCheck();
                $footer->disableEditingCheck();
                $footer->disableCreatingCheck();
            });
        });
    }
}
