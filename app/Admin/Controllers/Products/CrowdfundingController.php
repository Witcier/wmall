<?php

namespace App\Admin\Controllers\Products;

use App\Models\Product\Crowdfunding;
use App\Models\Product\Product;

class CrowdfundingController extends CommonController
{
    public function getProductType()
    {
        return Product::TYPE_CROWDFUNDING;
    }

    protected function customGrid(\Dcat\Admin\Grid $grid)
    {
        $grid->model()->with(['crowdfunding']);
        $grid->column('id')->sortable();
        $grid->column('title');
        $grid->column('category.name');
        $grid->column('image')->image('', 80, 80);
        $grid->column('price');
        $grid->column('on_sale')->switch();
        $grid->column('crowdfunding.user_count');
        $grid->column('progress')->display(function ($value) {
            return "{$this->crowdfunding->total_amount} / {$this->crowdfunding->target_amount}";
        });
        $grid->column('crowdfunding.end_at');
        $grid->column('crowdfunding.status')->using(Crowdfunding::$statusMap)
                ->label([
                    '1' => 'yellow',
                    '2' => 'green',
                    '3' => 'red',
                ]);
    }

    protected function customForm(\Dcat\Admin\Form $form)
    {
        $form->text('crowdfunding.target_amount')->rules('required|numeric|min:1');
        $form->datetime('crowdfunding.end_at')->rules('required|date');
    }
}
