<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    const DELETED_AT = 'blocked_at';

    protected $casts = [
        'score' => 'integer'
    ];

    public function like() {
        return $this->hasOne(PostLike::class);
    }


    public function likes() {
        return $this->hasMany(PostLike::class);
    }

    public function reports() {
        return $this->hasMany(PostReport::class);
    }

    public function votes() {
        return $this->hasMany(PostVote::class);
    }

}
