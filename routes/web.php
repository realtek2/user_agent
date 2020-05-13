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
    return view('site.main');
})->name('main');

Route::post('/telegram/webhook', 'TelegramController@webHook');
Route::get('/telegram/webhook', 'TelegramController@webHook');

Route::post('/tglogin', 'TgAuthController@login');
Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
Route::get('addsite', 'HomeController@addSite');
Route::post('/savesite', 'HomeController@saveSite');
Route::get('/deletesite/{id}', 'HomeController@deleteSite');
Route::get('showactions/{id}', 'HomeController@showActions');
Route::get('/client/{id}', 'HomeController@client');

Route::post('/getdata','ApiController@getData');