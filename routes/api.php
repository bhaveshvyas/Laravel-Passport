<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\ProductController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
 */

Route::group(['middleware' => 'api', 'prefix' => 'v1.1'], function () {

    Route::group(['controller' => AuthController::class], function () {
        Route::post('login', 'login');
        Route::post('register', 'register');
        Route::post('logout', 'logout');
        Route::post('forget_password', 'forgetPassword');
        Route::post('reset_password', 'resetPassword');
    });

    Route::middleware('auth:api')->group(function () {
        Route::resource('products', ProductController::class);
    });
});
