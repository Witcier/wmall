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
// 商品详情
Route::get('products/{product}', 'Products\ProductsController@show')->name('products.show');

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

    // 收藏商品
    Route::post('products/{product}/favorite', 'Products\ProductsController@favor')->name('products.favor');
    // 取消收藏
    Route::delete('products/{product}/favorite', 'Products\ProductsController@disfavor')->name('products.disfavor');
});
