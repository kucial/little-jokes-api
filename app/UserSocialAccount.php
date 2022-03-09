<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class UserSocialAccount extends Model
{
    protected $casts = [
        'info' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
