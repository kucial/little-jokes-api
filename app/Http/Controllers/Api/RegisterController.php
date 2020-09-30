<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Notifications\PhoneRegister;
use App\User;
use App\Http\Resources\User as UserResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RegisterController extends Controller
{
    public function sendCode(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => 'required'
        ]);

        $exists = DB::table('users')->where('mobile', $validatedData['phone'])
            ->exists();

        if ($exists) {
            return response()->json([
                'code' => 'REGISTERED',
                'message' => '手机号码已注册'
            ], 422);
        }

        $code = Cache::remember(
            $this->getCacheKey($validatedData['phone']),
            env('VERIFICATION_CODE_TTL'),
            function() {
                return strval(mt_rand(100000, 999999));
            }
        );

        // send code
        Notification::route('dysms', $validatedData['phone'])
            ->notify(new PhoneRegister($code));

        if (env('APP_DEBUG')) {
            return response()->json([
                'data' => [
                    'code' => $code,
                ]
            ]);
        }

        return response()->status(200);

    }

    public function phoneRegister(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => 'required', // should be E164 phone number
            'code' => 'required',
            'password' => 'sometimes',
            'region' => 'sometimes|default:CN'
        ]);

        $exists = DB::table('users')->where('mobile', $validatedData['phone'])
            ->exists();
        if ($exists) {
            return response()->json([
                'code' => 'REGISTERED',
                'message' => '手机号码已注册'
            ], 422);
        }

        // verify;
        $cacheKey = $this->getCacheKey($validatedData['phone']);
        $cachedCode = Cache::get($cacheKey);

        if ($cachedCode !== $validatedData['code']) {
            return response()->json([
                'code' => 'INVALID_CODE',
                'message' => '验证码不正确'
            ], 422);
        }

        Cache::forget($cacheKey);

        $user = new User();
        $user->mobile = $validatedData['phone'];
        // $user->region = $validatedData['region'];
        $password = Arr::get($validatedData, 'password',
            $validatedData['phone'].'.'.$validatedData['code']
        );
        $user->password = $password;
        $user->api_token = User::generateToken();
        $user->name = $validatedData['phone'];
        $user->save();

          return (new UserResource($user))->additional(['meta' => [
                'api_token' => $user->api_token,
            ]]);

    }

    protected function getCacheKey($phone)
    {
        return 'register.'.$phone;
    }

}
