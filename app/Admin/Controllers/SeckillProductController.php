<?php

namespace App\Admin\Controllers;

use App\Models\Product as AppProduct;

class SeckillProductController extends CommonProductController
{
    public function getProductType()
    {
        return AppProduct::TYPE_SECKILL;
    }

    protected function customGrid(\Dcat\Admin\Grid $grid)
    {
        $grid->column('id')->sortable();
        $grid->column('title');
        $grid->image()->image('',80,80);
        $grid->column('status')->switch();
        $grid->column('price');
        $grid->column('sold_count');
        // 展示秒杀商品相关字段
        $grid->column('seckill.start_at', '开始时间');
        $grid->column('seckill.end_at', '结束时间');
    }

    protected function customForm(\Dcat\Admin\Form $form)
    {
        $form->datetime('seckill.start_at', '开始时间')->rules('required|date');
        $form->datetime('seckill.end_at', '结束时间')->rules('required|date');
    }
}
