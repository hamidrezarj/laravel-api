<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\UserApiController;

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


Route::post('register', [RegisterController::class, 'register']);
Route::post('code', [RegisterController::class, 'createOrUpdateVerificationCode']);
Route::get('get_token', [RegisterController::class, 'getAccessToken']);
Route::get('get_code', [RegisterController::class, 'getCode']);

Route::middleware('auth:api')->group(function () {
    Route::get('user', [UserApiController::class, 'show']);
    Route::post('user/update/profile', [UserApiController::class, 'update']);
    Route::post('user/update/set_password', [UserApiController::class, 'set_password']);
});
