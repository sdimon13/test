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

Route::get('/', function () {
    return view('welcome');
});
Route::group(['prefix' => 'ebay', 'namespace' => 'Ebay', 'middleware' => 'auth'], function () {
    Route::get('/', 'EbayController@index');
    Route::get('add', 'EbayFindItemsController@findItemsAdvanced');
    Route::get('sellers', 'EbayController@sellers')->name('sellers');
    Route::get('products', 'EbayController@products')->name('products');
    Route::get('test', 'EbayController@test');
    Route::get('keywords', 'EbayFindItemsController@index')->name('keywords');
    Route::get('check', 'EbayFindItemsController@checkCount')->name('checkCount');
    Route::get('parse', 'EbayFindItemsController@add')->name('parse');
});
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
