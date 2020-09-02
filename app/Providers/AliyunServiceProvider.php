<?php

namespace App\Providers;

use AlibabaCloud\Client\AlibabaCloud;
use Illuminate\Support\ServiceProvider;

class AliyunServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        AlibabaCloud::accessKeyClient(
            env('ALIYUN_ACCESS_KEY'),
            env('ALIYUN_ACCESS_SECRET')
        )->regionId(env('ALIYUN_REGION_ID'))
            ->asDefaultClient();
    }
}
