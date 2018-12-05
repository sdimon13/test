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
    Route::get('add', 'EbayController@findItemsAdvanced');
    Route::get('sellers', 'EbayController@sellers');
    Route::get('products', 'EbayController@products');
    Route::get('test', 'EbayController@test');
});
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
