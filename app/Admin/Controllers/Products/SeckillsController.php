<?php

namespace App\Admin\Controllers\Products;

use App\Models\Product\Product;
use App\Models\Product\Sku;
use Illuminate\Support\Facades\Redis;

class SeckillsController extends CommonController
{
    public function getProductType()
    {
        return Product::TYPE_SECKILL;
    }

    protected function customGrid(\Dcat\Admin\Grid $grid)
    {
        $grid->model()->with(['seckill']);
        $grid->column('id')->sortable();
        $grid->column('title');
        $grid->column('category.name');
        $grid->column('image')->image('', 80, 80);
        $grid->column('price');
        $grid->column('sold_count');
        $grid->column('on_sale')->switch();
        $grid->column('seckill.start_at');
        $grid->column('seckill.end_at');
    }

    protected function customForm(\Dcat\Admin\Form $form)
    {
        $form->datetime('seckill.start_at')->rules('required|date');
        $form->datetime('seckill.end_at')->rules('required|date');

        $form->saved(function (\Dcat\Admin\Form $form) {
            $product = $form->isCreating() ? Product::find($form->getKey()) : $form->model();
            $product->load(['seckill', 'skus']);
            $diff = $product->seckill->end_at->getTimestamp() - time();

            $product->skus->each(function (Sku $sku) use ($diff, $product) {
                if ($product->on_sale && $diff > 0) {
                    Redis::setex('seckill_sku_' . $sku->id, $diff, $sku->stock);
                } else {
                    Redis::del('seckill_sku_' . $sku->id);
                }
            });
        });
    }
}
