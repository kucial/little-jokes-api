<?php

namespace App\Providers;

use App\Policies\LikePolicy;
use App\Policies\PostPolicy;
use App\Post;
use App\PostLike;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

use App\Services\AppleToken;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
        PostLike::class => LikePolicy::class,
        Post::class => PostPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //

        $this->app->bind(Configuration::class, fn () => Configuration::forSymmetricSigner(
            Sha256::create(),
            InMemory::file(storage_path(config('services.apple.private_key'))),
        ));
    }
}
