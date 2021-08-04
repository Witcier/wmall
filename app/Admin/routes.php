<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'     => config('admin.route.prefix'),
    'namespace'  => config('admin.route.namespace'),
    'middleware' => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    // 用户管理
    $router->resource('users', 'UsersController');

    // 商品管理
    $router->namespace('Products')
        ->group(function (Router $router) {
            // 普通商品
            $router->resource('products', 'ProductsController');
            // 众筹商品
            $router->resource('product/crowdfunding', 'CrowdfundingController');
            // 分类管理
            $router->resource('product/categories', 'CategoriesController');
        });

    // 订单管理
    $router->namespace('Orders')
        ->group(function (Router $router) {
            $router->get('orders', 'OrdersController@index')->name('orders.index');
            $router->get('orders/{id}', 'OrdersController@show')->name('orders.show');

            // 订单发货
            $router->post('orders/{order}/ship', 'OrdersController@ship')->name('orders.ship');

            // 退款处理
            $router->post('orders/{order}/refund', 'OrdersController@handleRefund')->name('orders.refund');
        });

    // 优惠卷管理
    $router->namespace('Coupons')
        ->prefix('coupons')
        ->group(function (Router $router) {
            $router->resource('codes', 'CodesController');
        });
});
