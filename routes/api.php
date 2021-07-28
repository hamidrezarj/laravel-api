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
Route::post('get_token', [RegisterController::class, 'getAccessToken']);
Route::post('get_code', [RegisterController::class, 'getCode']);

Route::middleware('auth:api')->group(function () {
    Route::get('user', [UserApiController::class, 'show']);
});
