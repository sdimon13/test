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
Route::get('ebay', 'EbayController@index');
Route::get('ebay/add', 'EbayController@findItemsAdvanced');
Route::get('ebay/get', 'EbayController@getUserInfo');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
