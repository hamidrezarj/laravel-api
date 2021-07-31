<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RegisterController extends Controller
{

    /**
     * Api which recieves phone number and creates 4digits random code available for 2mins or updates existing code.
     * @return Illuminate\Http\Response
     */
    public function createOrUpdateVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => ['required', 'digits:11', 'regex:/^(0){1}9\d{9}$/']
        ]);

        $message = '';
        if ($validator->fails()) {
            $status = 400;
            $success = false;

            return response()->json([
                'success' => $success,
                'message' => $validator->errors(),
            ], $status);
        }

        # if phone number already exists return update expire time and then return its corresponding verification code.
        if (User::where('phone', $request->phone)->exists()) {
            $user = User::where('phone', $request->phone)->first();
            $verification_code = VerificationCode::where('user_id', $user->id)->first();

            # if code has been expired, generate new one.
            if (Carbon::now()->isAfter($verification_code->expires_at)) {
                $verification_code->code = rand(1000, 10000);
            }

            $verification_code->expires_at = Carbon::now()->add('minutes', 2);
            $verification_code->save();

            return response()->json([
                'success' => true,
                'message' => 'code created successfully.',
            ]);
        }

        $message = 'code created successfully.';
        $user = new User;
        $user->phone = $request->phone;
        $user->save();
        $expires_at = Carbon::now()->add('minutes', 2);
        $code = new VerificationCode([
            'code' => rand(1000, 10000),
            'expires_at' => $expires_at,

        ]);
        $user->verification_code()->save($code);

        return response()->json([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * input(in query string format) : phone
     * out   : code 
     */
    public function getCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:11|regex:/^(0){1}9\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->errors(),
            ], 400);
        }

        # if user with requested phone number doesn't exist raise error.
        if (!User::where('phone', $request->phone)->exists()) {

            $data['error'] = "user with this phone number doesn't exist!";
            return response()->json([
                'success' => false,
                'data' => $data

            ], 400);
        }

        $user = User::where('phone', $request->phone)->first();
        $verification_code = VerificationCode::where('user_id', $user->id)->first();

        if ($verification_code->code == -1 || Carbon::now()->isAfter($verification_code->expires_at)) {
            $success = false;
            $data['error'] = 'code has been used or expired!';
        } else {
            $success = true;
            $data['code'] = $verification_code->code;
        }

        return response()->json([
            'success' => $success,
            'data' => $data,
        ]);
    }

    /**
     * input :   phone, code
     * output:   access token
     */
    public function getAccessToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|digits:4',
            'phone' => 'required|digits:11|regex:/^(0){1}9\d{9}$/',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->errors(),
            ], 400);
        }

        # if user with requested phone number doesn't exist raise error.
        if (!User::where('phone', $request->phone)->exists()) {

            $data['error'] = "user with this phone number doesn't exist!";
            return response()->json([
                'success' => false,
                'data' => $data

            ], 400);
        }

        $user = User::where('phone', $request->phone)->first();
        $verification_code = VerificationCode::where('user_id', $user->id)->first();

        if ($verification_code->code == $request->code && !Carbon::now()->isAfter($verification_code->expires_at)) {
            $data['token'] = $user->createToken('loginToken')->accessToken;
            $verification_code->access_token = $data['token'];

            # make code invalid to prevent security issues.
            $verification_code->code = -1;
            $verification_code->save();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } else {
            $data['error'] = 'invalid code!';

            return response()->json([
                'success' => false,
                'data' => $data

            ], 401);
        }
    }

    public function getUserDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:11|regex:/^(0){1}9\d{9}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors(),
            ], 400);
        }

        # if user with requested phone number doesn't exist raise error.
        if (!User::where('phone', $request->phone)->exists()) {

            $data['error'] = "user with this phone number doesn't exist!";
            return response()->json([
                'success' => false,
                'data' => $data

            ], 400);
        }

        $user = User::where('phone', $request->phone)->first();

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }
}
