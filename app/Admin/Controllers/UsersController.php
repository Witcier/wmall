<?php

namespace App\Admin\Controllers;

use App\Models\User\User;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;
use Dcat\Admin\Show;
use Dcat\Admin\Http\Controllers\AdminController;

class UsersController extends AdminController
{
    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        return Grid::make(new User(), function (Grid $grid) {
            $grid->column('id')->sortable();
            $grid->column('name');
            $grid->column('email');
            $grid->column('email_verified_at')->display(function ($value) {
                return $value ? '是' : '否';
            });
        
            $grid->toolsWithOutline(false);
            $grid->disableActions();
            $grid->disableCreateButton();
            $grid->disableBatchActions();

            $grid->filter(function (Grid\Filter $filter) {
                $filter->panel();

                $filter->equal('name')->width(3);
            });
        });
    }
}
