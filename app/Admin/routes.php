<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');

    // 用户管理
    $router->resource('users','UsersController');

    // 商品管理
    $router->resource('products','ProductsController');

    // 订单管理
    $router->resource('orders','OrderController');
    // 订单发货
    $router->post('orders/{order}/ship','OrderController@ship')->name('admin.orders.ship');
    // 订单退款
    $router->post('orders/{order}/refund','OrderController@handleRefund')->name('admin.orders.handle_refund');
});
