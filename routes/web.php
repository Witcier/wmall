<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Auth::routes(['verify' => true]);

// 首页
Route::redirect('/', '/products')->name('index');

// 商品列表
Route::get('products', 'Products\ProductsController@index')->name('products.index');

Route::group(['middleware' => ['auth', 'verified']], function () {
    // 收货地址
    Route::prefix('user/addresses')
        ->namespace('User')
        ->name('user.addresses.')
        ->group(function () {
            Route::get('/', 'AddressesController@index')->name('index');
            Route::get('create', 'AddressesController@create')->name('create');
            Route::post('/', 'AddressesController@store')->name('store');
            Route::get('{address}', 'AddressesController@edit')->name('edit');
            Route::put('{address}', 'AddressesController@update')->name('update');
            Route::delete('{address}', 'AddressesController@destroy')->name('destroy');
    });

    // 用户商品收藏列表
    Route::get('products/favorites', 'Products\ProductsController@favorites')->name('products.favorites');
    // 收藏商品
    Route::post('products/{product}/favorite', 'Products\ProductsController@favor')->name('products.favor');
    // 取消收藏
    Route::delete('products/{product}/favorite', 'Products\ProductsController@disfavor')->name('products.disfavor');

    // 加入购物车
    Route::post('cart', 'Cart\CartController@add')->name('cart.add');
    // 查看购物车
    Route::get('cart', 'Cart\CartController@index')->name('cart.index');
    // 移除购物车商品
    Route::delete('cart/{sku}', 'Cart\CartController@remove')->name('cart.remove');

    // 用户订单列表
    Route::get('orders', 'Order\OrdersController@index')->name('orders.index');
    // 订单详情
    Route::get('orders/{order}', 'Order\OrdersController@show')->name('orders.show');
    // 下单
    Route::post('orders', 'Order\OrdersController@store')->name('orders.store');

    // 支付宝支付
    Route::get('payment/{order}/alipay', 'Pay\PaymentController@payByAlipay')->name('payment.alipay');
    // 支付宝支付回调
    Route::get('payment/alipay/return', 'Pay\PaymentController@alipayReturn')->name('payment.alipay.return');
});

// 商品详情
Route::get('products/{product}', 'Products\ProductsController@show')->name('products.show');

// 支付宝支付服务端回调
Route::post('payment/alipay/notify', 'Pay\PaymentController@alipayNotify')->name('payment.alipay.notify');
