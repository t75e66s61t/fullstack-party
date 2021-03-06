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
})->name('home');

Route::group(['middleware'=>'guest'], function() {
    Route::get('login/github/', 'Auth\LoginController@redirectToProvider')->name('login');
    Route::get('login/github/callback/', 'Auth\LoginController@handleProviderCallback')->name('callback');
});

Route::group(['middleware'=>'auth'], function() {
    Route::get('/git/{repo}/issues/', 'GitController@issues')->name('issues');
    Route::get('/git/{repo}/issues/{number}/comments/', 'GitController@comments')->name('comments');
    Route::get('/logout', 'Auth\LoginController@logout')->name('logout');
});