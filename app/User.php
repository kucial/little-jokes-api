<?php

namespace App;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Faker\Factory as Faker;

class User extends Authenticatable
{
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'api_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'mobile_verified_at' => 'datetime'
    ];

    static public function generateToken()
    {
        return Str::random(60);
    }

    static public function randomName()
    {
        $faker = Faker::create('zh_CN');
        return $faker->name;
    }

    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Hash::make($value);
    }

    public function likedPosts()
    {
        $userId = $this->id;
        return $this->belongsToMany(Post::class, 'post_likes')
            ->with([
                'like' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                }
            ]);
    }

    public function postReports()
    {
        return $this->hasMany(PostReport::class);
    }

    public function socialAccounts()
    {
        return $this->hasMany(UserSocialAccount::class);
    }
}
