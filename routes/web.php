<?php

use Carbon\Carbon;
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
    // 收货
    Route::post('orders/{order}/received', 'Order\OrdersController@received')->name('orders.received');
    // 评价
    Route::get('orders/{order}/review', 'Order\OrdersController@review')->name('orders.review.show');
    Route::post('orders/{order}/review', 'Order\OrdersController@reviewed')->name('orders.review.store');

    // 众筹商品下单
    Route::post('order/crowdfunding', 'Order\OrdersController@crowdfunding')->name('order.crowdfunding.store');

    // 检查优惠卷优惠码
    Route::get('coupon/codes/{code}', 'Coupon\CodesController@show')->name('coupon.codes.show');

    // 申请退款
    Route::post('orders/{order}/refund', 'Order\OrdersController@applyRefund')->name('orders.refund');

    // 支付宝支付
    Route::get('payment/{order}/alipay', 'Pay\PaymentController@payByAlipay')->name('payment.alipay');
    // 支付宝支付回调
    Route::get('payment/alipay/return', 'Pay\PaymentController@alipayReturn')->name('payment.alipay.return');

    // 微信支付
    Route::get('payment/{order}/wechat', 'Pay\PaymentController@payByWechat')->name('payment.wechat');

    // 分期付款
    Route::post('payment/{order}/installment', 'Pay\PaymentController@payByInstallment')->name('payment.installment');

    // 分期付款订单列表
    Route::get('installments', 'Installment\InstallmentsController@index')->name('installments.index');
    // 详情
    Route::get('installments/{installment}', 'Installment\InstallmentsController@show')->name('installments.show');
    // 分期付款支付宝还款
    Route::get('installments/{installment}/alipay', 'Installment\InstallmentsController@payByAlipay')->name('installments.alipay');
    // 回调
    Route::get('installments/alipay/return', 'Installment\InstallmentsController@alipayReturn')->name('installments.alipay.return');
    // 分期付款微信还款
    Route::get('installments/{installment}/wechat', 'Installment\InstallmentsController@payByWechat')->name('installments.wechat');
});

// 商品详情
Route::get('products/{product}', 'Products\ProductsController@show')->name('products.show');

// 支付宝支付服务端回调
Route::post('payment/alipay/notify', 'Pay\PaymentController@alipayNotify')->name('payment.alipay.notify');
// 微信支付服务端回调
Route::post('payment/wechat/notify', 'Pay\PaymentController@wechatNotify')->name('payment.wechat.notify');

// 分期付款支付宝还款服务端回调
Route::post('installments/alipay/notify', 'Installment\InstallmentsController@alipayNotify')->name('installments.alipay.notify');
// 分期付款微信还款服务端回调
Route::post('installments/wechat/notify', 'Installment\InstallmentsController@wechatNotify')->name('installments.wechat.notify');

// 微信退款回调
Route::post('payment/wechat/refund_notify', 'PaymentController@wechatRefundNotify')->name('payment.wechat.refund_notify');
// 分期付款微信退款回调
Route::post('installments/wechat/refund_notify', 'InstallmentsController@wechatRefundNotify')->name('installments.wechat.refund_notify');