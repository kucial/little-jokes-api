<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostVote extends Model
{
    const UP_VOTE = 1;
    const DOWN_VOTE = -1;

    protected $guarded = ['id', 'vote_type'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function post() {
        return $this->belongsTo(Post::class);
    }
}
