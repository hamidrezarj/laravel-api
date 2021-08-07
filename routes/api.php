<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RegisterController;
use App\Http\Controllers\API\UserApiController;
use App\Models\User;
use Ghasedak\GhasedakApi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

Route::post('code', [RegisterController::class, 'createOrUpdateVerificationCode']);
Route::get('get_token', [RegisterController::class, 'getAccessToken']);
Route::get('get_code', [RegisterController::class, 'getCode']);

Route::post('send', [UserApiController::class, 'send_notif']);

Route::middleware('auth:api')->group(function () {
    Route::get('user', [UserApiController::class, 'show'])->name('user');
    Route::post('user/update/profile', [UserApiController::class, 'update']);
    Route::post('user/update/set_password', [UserApiController::class, 'set_password']);

    Route::post('logout', [RegisterController::class, 'logout']);
});

Route::post('ghasedak', function () {
    $api = new GhasedakApi(env('GHASEDAKAPI_KEY'));
    return $api->AccountInfo();
});

Route::get('pass_check', function (Request $request) {

    if ($request->has('phone')) {
        $builder = User::where('phone', $request->phone);
        if (!$builder->exists())
            return 'user not found';
        $user = User::where('phone', $request->phone)->first();
        $options['rounds'] = 12;
        // dd($user->password == bcrypt($request->password, $options));
        dd(Hash::check($request->password, $user->password));
    } else {
        return "no phone added";
    }
});

/** MAIN CODE FOR OAUTH2 */
Route::post('get_oauth_token', [RegisterController::class, 'getOauthToken']);

