<?php

namespace App\Http\Controllers\Api;

use App\Channels\DySMSChannel;
use App\Http\Controllers\Controller;
use App\Notifications\PhoneLogin;
use App\User;
use App\Http\Resources\User as UserResource;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\Eloquent\Concerns\HasAttributes;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class LoginController extends Controller
{

    public function sendCode(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => 'required|exists:users,mobile'
        ]);
        $code = Cache::remember($this->getCacheKey($validatedData['phone']), env('LOGIN_CODE_TTL'), function() {
            return strval(mt_rand(100000, 999999));
        });

        // send code
        Notification::route('dysms', $validatedData['phone'])
            ->notify(new PhoneLogin($code));

        if (env('APP_DEBUG')) {
            return response()->json([
                'data' => [
                    'code' => $code
                ]
            ]);
        }

        return response()->status(200);
    }

    public function withPhoneCode(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => 'required|exists:users,mobile',
            'code' => 'required',
        ]);

        $cachedCode = Cache::get(
            $this->getCacheKey($validatedData['phone']));

        if ($cachedCode !== $validatedData['code']) {
            return response()->json([
                'code' => 'INVALID_CODE',
                'message' => '验证码错误'
            ], 422);
        }

        $user = User::where('mobile', $validatedData['phone'])->first();

        return $this->loginSuccess($user);
    }

    public function withPhonePassword(Request $request, Hasher $hasher)
    {
        $validatedData = $request->validate([
            'phone' => 'required|exists:users,mobile',
            'password' => 'required',
        ]);

        $user = User::where('mobile', $validatedData['phone'])->first();
        $isValid = $hasher->check($validatedData['password'], $user->getAuthPassword());
        if ($isValid) {
            return $this->loginSuccess($user);
        }

        return response()->json([
            'code' => 'INVALID_CREDENTIALS',
            'message' => '账号或密码不正确'
        ], 403);

    }

    protected function getCacheKey($phone)
    {
        return 'login.'.$phone;
    }

    protected function loginSuccess($user)
    {
        return (new UserResource($user))->additional(['meta' => [
            'api_token' => $user->api_token,
        ]]);
    }

//    public function getGithubAuthorizeLink()
//    {
//        dd(Socialite::driver('github')->redirect());
//    }

    public function withOauthCode(Request $request)
    {
        $provider = $request->query('provider');
        $oauthUser = Socialite::driver($provider)->stateless()->user();
        switch ($provider) {
            case 'github':
                Log::info('github user: '. json_encode($oauthUser));
                return $this->handleGithubCallback($oauthUser);
            default:
                return response()->json([
                    'code' => 'UNKNOWN_PROVIDER'
                ], 400);
        }
    }

    public function handleGithubCallback($oauthUser)
    {
        $user = User::where('email', $oauthUser->email)->first();
        if (is_null($user)) {
            $user = new User();
            $user->name = $oauthUser->nickname;
            $user->password = Str::random(16);
            $user->email = $oauthUser->email;
            $user->api_token = User::generateToken();
            $user->save();
        }

        return $this->loginSuccess($user);
    }

    public function handleWeixinProviderCallback(Request $request)
    {
        $oauthUser = Socialite::driver('weixin')->stateless()->user();
        /**
        {
        "accessTokenResponseBody": {
        "access_token": "36_SrT6x04Piw0BBzTSVm0gDU9c2qOnOrHNQG-rqnDO9FNwiNg7biXI-_OoIOyOvkclvAGs4zR_0A4aFHuKMysGKNOGfKzs6w8sVuKZ08-kZmk",
        "expires_in": 7200,
        "refresh_token": "36_FgKwDvRaXvNBEPKa7Q0wYbWNCd74PJRlDt0TT_dpgLdNrm6G_K_xK4bojz7O9p5D1IRwDmUQ2Y4AzC0Z9lvK_jizAx59OU4zmH_MPsBJmCU",
        "openid": "omgKG6CvJeegNT6h8dSB5ju_hhCw",
        "scope": "snsapi_userinfo"
        },
        "token": "36_SrT6x04Piw0BBzTSVm0gDU9c2qOnOrHNQG-rqnDO9FNwiNg7biXI-_OoIOyOvkclvAGs4zR_0A4aFHuKMysGKNOGfKzs6w8sVuKZ08-kZmk",
        "refreshToken": "36_FgKwDvRaXvNBEPKa7Q0wYbWNCd74PJRlDt0TT_dpgLdNrm6G_K_xK4bojz7O9p5D1IRwDmUQ2Y4AzC0Z9lvK_jizAx59OU4zmH_MPsBJmCU",
        "expiresIn": 7200,
        "id": "omgKG6CvJeegNT6h8dSB5ju_hhCw",
        "nickname": "\u4f1f\u592a",
        "name": null,
        "email": null,
        "avatar": "http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTK4QvRe9wjL3gQ2O1FG4Erc4qR5dhO3ibo59nfu5iaF65HY6rYvVfGYN70HlTohhoNrDlAiagwjlicX1g/132",
        "user": {
        "openid": "omgKG6CvJeegNT6h8dSB5ju_hhCw",
        "nickname": "\u4f1f\u592a",
        "sex": 1,
        "language": "zh_CN",
        "city": "\u4f5b\u5c71",
        "province": "\u5e7f\u4e1c",
        "country": "\u4e2d\u56fd",
        "headimgurl": "http://thirdwx.qlogo.cn/mmopen/vi_32/Q0j4TwGTfTK4QvRe9wjL3gQ2O1FG4Erc4qR5dhO3ibo59nfu5iaF65HY6rYvVfGYN70HlTohhoNrDlAiagwjlicX1g/132",
        "privilege": []
        }
        }
         */
        $user = User::where('wechat_open_id', $oauthUser->id)->first();
        if (is_null($user)) {
            $user = new User();
            $user->name = $oauthUser->nickname;
            $user->password = $oauthUser->id;
            $user->wechat_open_id = $oauthUser->id;
            $user->api_token = User::generateToken();
            $user->save();
        }

        return response()->json([
            'api_token' => $user->api_token,
            'next' => $request->query('next', '')
        ]);
    }
}
