<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Storage;
use App\Channels\DySMSChannel;
use App\Http\Controllers\Controller;
use App\Notifications\PhoneLogin;
use App\User;
use App\Http\Resources\User as UserResource;
use App\Services\AppleToken;
use App\UserSocialAccount;
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
use Google\Client as GoogleClient;

class LoginController extends Controller
{

    public function sendCode(Request $request)
    {
        $validatedData = $request->validate([
            'phone' => 'required|exists:users,mobile'
        ]);
        $code = Cache::remember($this->getCacheKey($validatedData['phone']), env('LOGIN_CODE_TTL'), function () {
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

        $cacheKey = $this->getCacheKey($validatedData['phone']);
        $cachedCode = Cache::get($cacheKey);

        if ($cachedCode !== $validatedData['code']) {
            return response()->json([
                'code' => 'INVALID_CODE',
                'message' => '验证码错误'
            ], 422);
        }

        Cache::forget($cacheKey);

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
        return 'login.' . $phone;
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
                Log::info('github user: ' . json_encode($oauthUser));
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
        $socialAccount = UserSocialAccount::where('provider', 'wechat')
            ->where('client_id', config('services.weixin.client_id'))
            ->where('openid', $oauthUser->id)
            ->with('user')
            ->first();

        if (is_null($socialAccount)) {
            $user = new User();
            $user->name = $oauthUser->nickname;
            $user->password = $oauthUser->id;
            $user->api_token = User::generateToken();
            $user->save();

            $socialAccount = new UserSocialAccount();
            $socialAccount->user_id = $user->id;
            $socialAccount->openid = $oauthUser->id;
            $socialAccount->provider = 'wechat';
            $socialAccount->client_id = config('services.weixin.client_id');
            $socialAccount->username = $oauthUser->nickname;
            $socialAccount->access_token = $oauthUser->token;
            $socialAccount->refresh_token = $oauthUser->refreshToken;
            $socialAccount->info = $oauthUser->user;
            $socialAccount->save();

            return $this->loginSuccess($user);
        } else {
            $socialAccount->username = $oauthUser->nickname;
            $socialAccount->access_token = $oauthUser->token;
            $socialAccount->refresh_token = $oauthUser->refreshToken;
            $socialAccount->info = $oauthUser->user;
            $socialAccount->save();

            return $this->loginSuccess($socialAccount->user);
        }
    }

    public function withGoogleIdToken(Request $request)
    {
        $validatedData = $request->validate([
            'clientId' => 'required',
            'idToken' => 'required',
        ]);
        $client = new GoogleClient([
            'client_id' => $validatedData['clientId'],
        ]);

        $payload = $client->verifyIdToken($validatedData['idToken']);

        // {
        //     "at_hash": "Hyq4kXVZ_OFgQyWU-mtBng",
        //     "aud": "519686545414-pe1fh36s83f9p7c6kk1tgm31qthd5dre.apps.googleusercontent.com",
        //     "azp": "519686545414-pe1fh36s83f9p7c6kk1tgm31qthd5dre.apps.googleusercontent.com",
        //     "email": "kongkx.yang@gmail.com",
        //     "email_verified": true,
        //     "exp": 1646150027,
        //     "family_name": "yang",
        //     "given_name": "kongkx",
        //     "iat": 1646146427,
        //     "iss": "https://accounts.google.com",
        //     "locale": "zh-CN",
        //     "name": "kongkx yang",
        //     "nonce": "gxCBqVrN2q_9dX2pR0QLtaVo3gj3GrsfUOGeCacgJF0",
        //     "picture": "https://lh3.googleusercontent.com/a/AATXAJwnj8XDQuxqkhgyn3m7pQtIrH8eoa4gL6_XLFAQ=s96-c",
        //     "sub": "100635365667200547749",
        //   }

        if ($payload) {
            $socialAccount = UserSocialAccount::where('provider', 'google')
                ->where('client_id', $validatedData['clientId'])
                ->where('openid', $payload['sub'])
                ->with('user')
                ->first();
            if (is_null($socialAccount)) {
                $user = new User();
                $user->name = $payload['name'];
                $user->password = $payload['sub'];
                $user->api_token = User::generateToken();
                $user->save();

                $socialAccount = new UserSocialAccount();
                $socialAccount->user_id = $user->id;
                $socialAccount->openid = $payload['sub'];
                $socialAccount->provider = 'google';
                $socialAccount->client_id = $validatedData['clientId'];
                $socialAccount->username = $payload['name'];
                $socialAccount->info = $payload;
                $socialAccount->save();
                return $this->loginSuccess($user);
            } else {
                $socialAccount->username = $payload['name'];
                $socialAccount->info = $payload;
                $socialAccount->save();

                return $this->loginSuccess($socialAccount->user);
            }
        } else {
            return response()->json([
                'code' => 'INVALID_TOKEN'
            ])->status(403);
        }
    }

    public function withAppleId(Request $request, AppleToken $appleToken)
    {
        $validatedData = $request->validate([
            'code' => 'required',
            'openid' => 'sometimes',
            'clientId' => 'sometimes',  // may support multi client
            'idToken' => 'sometimes',
        ]);


        try {
            // $clientSecret = Storage::disk('local')->get('apple_secret');
            // config()->set('services.apple.client_secret', $clientSecret);
            config()->set('services.apple.client_secret', $appleToken->generate());
            $oauthUser = Socialite::driver('apple')->stateless()->user();

            // {
            //     "accessTokenResponseBody": Object {
            //       "access_token": "a12f31bb3d06e4282af8401e320a50f51.0.rxxq.irFSSQH9I8wMe9DzCiVBDw",
            //       "expires_in": 3600,
            //       "id_token": "eyJraWQiOiJlWGF1bm1MIiwiYWxnIjoiUlMyNTYifQ.eyJpc3MiOiJodHRwczovL2FwcGxlaWQuYXBwbGUuY29tIiwiYXVkIjoiY29tLmt1Y2lhbC5saXR0bGUtam9rZXMiLCJleHAiOjE2NDQ4NDczMTMsImlhdCI6MTY0NDc2MDkxMywic3ViIjoiMDAwNzcwLjNmZGYyZmU4MzJiMTQwYjNiNWMxYjJjZjgzYTZmNmFiLjEyNTciLCJub25jZSI6IjU4N2ZlM2QwODc5NmNkY2UzOTc0ZDA1NDUxZjc3YWEwNjY3MTM3OWM2ODVjYWM2MWQxZDIwZmU0NTkzODYxODkiLCJhdF9oYXNoIjoiZXRuTDMtM2MzN3l2TkxPYjFaU3ByQSIsImVtYWlsIjoicmI2amo5Y3Q0aEBwcml2YXRlcmVsYXkuYXBwbGVpZC5jb20iLCJlbWFpbF92ZXJpZmllZCI6InRydWUiLCJpc19wcml2YXRlX2VtYWlsIjoidHJ1ZSIsImF1dGhfdGltZSI6MTY0NDc2MDkxMSwibm9uY2Vfc3VwcG9ydGVkIjp0cnVlfQ.0g0WZwTNFU9uvciaPl9zj7Eext4MOJGa7pWE3yu9rK2rd3v_3eFgm5fMUBNh1opXFAaxo-cnTwKgSOJidmucp7l9zEGch_NC4i8TDbs9EyOSNHWsmM5ij_oad_3sdUAjW383P160fCHCXVY1IiV0emt38uq_anxMA9qBKM9GcPIYIY-WWQhsfpz6zndOMGKTOvPVXVxJ213SbcJzaIJCdJw-kwLuts1QxhKr1mVZIy1-4FXFKHCqzvl9jopT9vTPKMM4ey_Nqi-i4Mp0VZuRerTHBbizgR7oRw9EQ3zSJlNFEVTftQejfBQ11gpNOlGgbj_VKqOfnK51gTRjyI0wTg",
            //       "refresh_token": "r471400302120413eb1a0ae1faf797065.0.rxxq.nkcL09uvtyVG_GA5vlmnwA",
            //       "token_type": "Bearer",
            //     },
            //     "approvedScopes": null,
            //     "avatar": null,
            //     "email": "rb6jj9ct4h@privaterelay.appleid.com",
            //     "expiresIn": 3600,
            //     "id": "000770.3fdf2fe832b140b3b5c1b2cf83a6f6ab.1257",
            //     "name": null,
            //     "nickname": null,
            //     "refreshToken": "r471400302120413eb1a0ae1faf797065.0.rxxq.nkcL09uvtyVG_GA5vlmnwA",
            //     "token": "eyJraWQiOiJlWGF1bm1MIiwiYWxnIjoiUlMyNTYifQ.eyJpc3MiOiJodHRwczovL2FwcGxlaWQuYXBwbGUuY29tIiwiYXVkIjoiY29tLmt1Y2lhbC5saXR0bGUtam9rZXMiLCJleHAiOjE2NDQ4NDczMTMsImlhdCI6MTY0NDc2MDkxMywic3ViIjoiMDAwNzcwLjNmZGYyZmU4MzJiMTQwYjNiNWMxYjJjZjgzYTZmNmFiLjEyNTciLCJub25jZSI6IjU4N2ZlM2QwODc5NmNkY2UzOTc0ZDA1NDUxZjc3YWEwNjY3MTM3OWM2ODVjYWM2MWQxZDIwZmU0NTkzODYxODkiLCJhdF9oYXNoIjoiZXRuTDMtM2MzN3l2TkxPYjFaU3ByQSIsImVtYWlsIjoicmI2amo5Y3Q0aEBwcml2YXRlcmVsYXkuYXBwbGVpZC5jb20iLCJlbWFpbF92ZXJpZmllZCI6InRydWUiLCJpc19wcml2YXRlX2VtYWlsIjoidHJ1ZSIsImF1dGhfdGltZSI6MTY0NDc2MDkxMSwibm9uY2Vfc3VwcG9ydGVkIjp0cnVlfQ.0g0WZwTNFU9uvciaPl9zj7Eext4MOJGa7pWE3yu9rK2rd3v_3eFgm5fMUBNh1opXFAaxo-cnTwKgSOJidmucp7l9zEGch_NC4i8TDbs9EyOSNHWsmM5ij_oad_3sdUAjW383P160fCHCXVY1IiV0emt38uq_anxMA9qBKM9GcPIYIY-WWQhsfpz6zndOMGKTOvPVXVxJ213SbcJzaIJCdJw-kwLuts1QxhKr1mVZIy1-4FXFKHCqzvl9jopT9vTPKMM4ey_Nqi-i4Mp0VZuRerTHBbizgR7oRw9EQ3zSJlNFEVTftQejfBQ11gpNOlGgbj_VKqOfnK51gTRjyI0wTg",
            //     "user": Object {
            //       "at_hash": "etnL3-3c37yvNLOb1ZSprA",
            //       "aud": "com.kucial.little-jokes",
            //       "auth_time": 1644760911,
            //       "email": "rb6jj9ct4h@privaterelay.appleid.com",
            //       "email_verified": "true",
            //       "exp": 1644847313,
            //       "iat": 1644760913,
            //       "is_private_email": "true",
            //       "iss": "https://appleid.apple.com",
            //       "nonce": "587fe3d08796cdce3974d05451f77aa06671379c685cac61d1d20fe459386189",
            //       "nonce_supported": true,
            //       "sub": "000770.3fdf2fe832b140b3b5c1b2cf83a6f6ab.1257",
            //     },
            //   }

            $socialAccount = UserSocialAccount::where('provider', 'apple')
                ->where('client_id', config()->get('services.apple.client_id'))
                ->where('openid', $oauthUser->id)
                ->with('user')
                ->first();

            if (is_null($socialAccount)) {
                $user = new User();
                $user->name = $oauthUser->name || 'apple';
                $user->password = $oauthUser->id;
                $user->api_token = User::generateToken();
                $user->save();

                $socialAccount = new UserSocialAccount();
                $socialAccount->user_id = $user->id;
                $socialAccount->openid = $oauthUser->id;
                $socialAccount->provider = 'apple';
                $socialAccount->client_id = config()->get('services.apple.client_id');
                $socialAccount->username = $oauthUser->name || 'apple';

                $socialAccount->access_token = $oauthUser->accessTokenResponseBody['access_token'];
                $socialAccount->refresh_token = $oauthUser->refreshToken;

                $socialAccount->info = $oauthUser->user;
                $socialAccount->save();
                return $this->loginSuccess($user);
            } else {
                $socialAccount->info = $oauthUser->user;
                $socialAccount->save();

                return $this->loginSuccess($socialAccount->user);
            }
        } catch (\Exception $e) {
            return response()->json([
                'code' => 'ERROR',
                'message' => $e->getMessage()
            ], 500);
        }




        // dd($oauthUser);
    }

    public function handleAppleCallback(Request $request)
    {
    }
}
