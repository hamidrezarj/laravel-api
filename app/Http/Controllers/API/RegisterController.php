<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Models\User;
use App\Http\Controllers\Controller;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class RegisterController extends Controller
{

    /**
     * Register Api
     * 
     * @return \Illuminate\Http\Response
     */
    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name'  => 'required|n',
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'data' => $validator->errors(),
            ], 400);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
        ]);

        $success['token'] = $user->createToken('firstApi')->accessToken;
        $success['name'] = $user->name;

        $token = $user->createToken('firstApi')->accessToken;;

        return response()->json([
            'success' => true,
            'data' => $success
        ], 200);
    }

    /**
     * Api which recieves phone number and creates 4digits random code available for 2mins or updates existing code.
     * @return Illuminate\Http\Response
     */
    public function createOrUpdateVerificationCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|unique:users|digits:11',
        ]);

        $message = '';
        if ($validator->fails()) {
            $status = 200;
            $success = false;
            $fields = '';
            $data = '';

            # if phone number already exists return update expire time and then return its corresponding verification code.
            if (isset($validator->failed()['phone']['Unique'])) {
                $user = User::where('phone', $request->phone)->first();

                $verification_code = VerificationCode::where('user_id', $user->id)->first();

                # if code has been expired, generate new one.
                if (Carbon::now()->isAfter($verification_code->expires_at)) {
                    $verification_code->code = rand(1000, 10000);
                }

                $verification_code->expires_at = Carbon::now()->add('minutes', 2);
                $verification_code->save();

                // $data = ['code' => $verification_code->code];
                $success = true;
                $message = 'code created successfully.';
                $status = 200;
            } else {
                $status = 400;
                $success = false;
                $message = "code hasn't been created due to error.";
                $fields = $validator->failed();
                $data = $validator->errors();
            }

            return response()->json([
                'success' => $success,
                'message' => $message,
                'data' => $data,
                'fields' => $fields,
            ], $status);
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
     * input : phone
     * out   : code 
     */
    public function getCode(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:11',
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
        
        if($verification_code->code == -1 || Carbon::now()->isAfter($verification_code->expires_at)){
            $success = false;
            $data['error'] = 'code has been used or expired!';
        }
        else{
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
            'phone' => 'required|digits:11',

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

    /**
     * input : phone, token 
     * output: user info 
     */
    public function authorizeToken(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:11',
        ]);

        if($validator->fails())
        {
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

        $header = $request->header('Authorization');
        
        if(empty($header))
        {
            $status = 401;
            $error = 'UnAuthorized!';
            return response()->json([
                'success' => false,
                'error' => $error,
            ], $status);
        }
        
        $input_token = explode(" ", $header)[1];
        $user = User::where('phone', $request->phone)->first();
        $verification_code = VerificationCode::where('user_id', $user->id)->first();
        
        # validate token 
        if($input_token != $verification_code->access_token)
        {
            $status = 401;
            $error = 'UnAuthorized!';
            return response()->json([
                'success' => false,
                'error' => $error,
            ], $status);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function getUserDetails(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|digits:11',
        ]);

        if($validator->fails())
        {
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
