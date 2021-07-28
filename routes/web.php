<?php

use App\Http\Controllers\API\UserApiController;
use App\Models\User;
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

Route::get('/', function () {
    return 'index view';
});

Route::get('/hello', function(){
    return 'HELLO MTF';
});

Route::resource('user', UserApiController::class);
