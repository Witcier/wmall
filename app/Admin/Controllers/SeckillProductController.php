<?php

namespace App\Admin\Controllers;

use App\Models\Product as AppProduct;
use App\Models\ProductSku;
use Dcat\Admin\Form;
use Illuminate\Support\Facades\Redis;

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

        // 当秒杀商品表单保存时触发
        $form->saved(function (Form $form) {
            $product = AppProduct::find($form->model()->id);
            // 商品重新加载秒杀 和 SKU 字段
            $product->load(['seckill', 'skus']);
            // 获取当前时间与秒杀结束时间的差值
            $diff = $product->seckill->end_at->getTimestamp() - time();

            // 遍历秒杀商品的 SKU
            $product->skus->each(function (ProductSku $sku) use ($diff, $product) {
                // 如果秒杀商品是上架并且还没到结束时间
                if ($product->status && $diff > 0) {
                    // 将剩余库存写到 Redis 中， 并设置该过期时间为秒杀截止时间
                    Redis::setex('seckill_sku_'.$sku->id, $diff, $sku->stock);
                } else {
                    // 否则将该 SKU 的库存从 Redis 中删除
                    Redis::del('seckill_sku_'.$sku->id);
                }
            });
        });
    }
}
