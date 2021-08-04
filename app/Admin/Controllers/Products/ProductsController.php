<?php

namespace App\Admin\Controllers\Products;

use App\Models\Product\Category;
use App\Models\Product\Product;
use Dcat\Admin\Form;
use Dcat\Admin\Grid;

class ProductsController extends CommonController
{
    public function getProductType()
    {
        return Product::TYPE_NORMAL;
    }

    protected function customGrid(\Dcat\Admin\Grid $grid)
    {
        $grid->column('id')->sortable();
        $grid->column('title');
        $grid->column('category.name');
        $grid->column('image')->image('', 80, 80);
        $grid->column('on_sale')->switch();
        $grid->column('rating');
        $grid->column('sold_count');
        $grid->column('review_count');
        $grid->column('price');
    }

    protected function customForm(\Dcat\Admin\Form $form)
    {
        
    }
}
