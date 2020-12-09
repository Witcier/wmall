<?php

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

use Illuminate\Support\Facades\Route;

Route::redirect('/', '/products')->name('index');
// 商城主页
Route::get('products','ProductsController@index')->name('products.index');


Auth::routes(['verify' => true]);

Route::group(['middleware' => ['auth']], function () {
    // 收货地址模块
    Route::get('user_addresses', 'UserAddressesController@index')->name('user_addresses.index');
    Route::get('user_addresses/create', 'UserAddressesController@create')->name('user_addresses.create');
    Route::post('user_addresses', 'UserAddressesController@store')->name('user_addresses.store');
    Route::get('user_addresses/{user_address}', 'UserAddressesController@edit')->name('user_addresses.edit');
    Route::put('user_addresses/{user_address}', 'UserAddressesController@update')->name('user_addresses.update');
    Route::delete('user_addresses/{user_address}', 'UserAddressesController@destroy')->name('user_addresses.destroy');

    // 商品收藏
    Route::get('products/favorites', 'ProductsController@favorites')->name('products.favorites');
    Route::post('products/{product}/favorite', 'ProductsController@favor')->name('products.favor');
    Route::delete('products/{product}/favorite', 'ProductsController@disfavor')->name('products.disfavor');

    // 购物车
    Route::get('cart', 'CartController@index')->name('cart.index');
    Route::post('cart', 'CartController@add')->name('cart.add');
    Route::delete('cart/{sku}', 'CartController@remove')->name('cart.remove');

    // 订单
    Route::get('orders', 'OrdersController@index')->name('orders.index');
    Route::post('orders', 'OrdersController@store')->name('orders.store');
    Route::get('orders/{order}', 'OrdersController@show')->name('orders.show');
    // 确认收货
    Route::post('orders/{order}/received','OrdersController@received')->name('orders.received');
    // 订单评价
    Route::get('orders/{order}/review','OrdersController@review')->name('orders.review.show');
    Route::post('orders/{order}/review','OrdersController@sendReview')->name('orders.review.store');
    // 申请退款
    Route::post('orders/{order}/apply_refund','OrdersController@applyRefund')->name('orders.apply_refund');

    // 支付宝支付
    Route::get('payment/{order}/alipay', 'PaymentController@payByAlipay')->name('payment.alipay');
    Route::get('payment/alipay/return', 'PaymentController@alipayReturn')->name('payment.alipay.return');

    // 微信支付
    Route::get('payment/{order}/wechat', 'PaymentController@payByWechat')->name('payment.wechat');

    // 优惠卷检测
    Route::get('coupon_codes/{code}', 'CouponCodesController@show')->name('coupon_codes.show');

    // 众筹商品下单
    Route::post('crowdfunding_orders','OrdersController@crowdfunding')->name('crowdfunding_orders.store');

    // 分期付款
    Route::post('payment/{order}/installment','PaymentController@payByInstallment')->name('payment.installment');

    // 分期付款列表
    Route::get('installments','InstallmentsController@index')->name('installments.index');

    // 分期返款详情
    Route::get('installments/{installment}','InstallmentsController@show')->name('installments.show');

    // 分期付款(支付宝)
    Route::get('installments/{installment}/alipay','InstallmentsController@payByAlipay')->name('installments.alipay');

    // 分期付款前端回调(支付宝)
    Route::get('installments/alipay/return','InstallmentsController@alipayReturn')->name('installments.alipay.return');

     // 分期付款(微信)
    Route::get('installments/{installment}/wechat','InstallmentsController@payByWechat')->name('installments.wechat');
});

//商品详情
Route::get('products/{product}','ProductsController@show')->name('products.show');

// 服务端回调
Route::post('payment/alipay/notify', 'PaymentController@alipayNotify')->name('payment.alipay.notify');
Route::post('payment/wechat/notify', 'PaymentController@wechatNotify')->name('payment.wechat.notify');
Route::post('payment/wechat/refund_notify', 'PaymentController@wechatRefundNotify')->name('payment.wechat.refund_notify');
Route::post('installments/alipay/notify', 'InstallmentsController@alipayNotify')->name('installments.alipay.notify');
Route::post('installments/wechat/notify', 'InstallmentsController@wechatNotify')->name('installments.wechat.notify');
Route::post('installments/wechat/refund_notify', 'InstallmentsController@wechatRefundNotify')->name('installments.wechat.refund_notify');

