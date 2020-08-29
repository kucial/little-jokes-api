<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group([
    'prefix' => 'posts',
], function() {
    Route::get('/{id}', 'PostController@view')->name('posts.detail');
    Route::middleware('auth:api')->group(function() {
        Route::post('/{id}/_like', 'PostController@like')->name('posts.like');
        Route::post('/{id}/_unlike', 'PostController@unlike')->name('posts.unlike');
        Route::post('/{id}/_report', 'PostController@report')->name('posts.report');
        Route::post('/{id}/_vote', 'PostController@vote')->name('posts.vote');
    });
});

Route::middleware('auth:api')->get('/users/{id}/liked-posts', 'PostController@userLiked')->name('users.liked-posts');

Route::group([
    'middleware' => 'auth:api'
], function() {
    Route::prefix('likes')->group(function() {
        Route::post('/{id}/_archive', 'LikeController@archive')->name('likes.archive');
        Route::post('/{id}/_unarchive', 'LikeController@unArchive')->name('likes.unarchive');
    });
});

Route::prefix('reports')->group(function() {
    Route::middleware('auth:api')->delete('/{id}', 'PostReportController@delete')->name('reports.delete');
});

Route::get('/feed/hottest', 'FeedController@hottest');
Route::get('/feed/latest', 'FeedController@latest');
Route::get('/feed/random', 'FeedController@random');

Route::group([
    'middleware' => 'guest',
    'prefix' => 'register'
], function() {

});

Route::group([
    'middleware' => 'guest',
    'prefix' => 'auth',
], function() {
    Route::post('/login/with_phone_password', 'LoginController@withPhonePassword');
    Route::post('/login/with_phone_code', 'LoginController@withPhoneCode');
    Route::post('/login/send_phone_code', 'LoginController@sendCode');

    Route::post('/register/send_phone_code', 'RegisterController@sendCode');
    Route::post('/register/with_phone_code', 'RegisterController@phoneRegister');
});
