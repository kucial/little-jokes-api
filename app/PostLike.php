<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PostLike extends Model
{

    protected $casts = [
        'archived_at' => 'datetime'
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function post() {
        return $this->belongsTo(Post::class);
    }

    public function hasArchived() {
        return !is_null($this->archived_at);
    }

    public function archive() {
        $this->archived_at = $this->freshTimestamp();
        return $this->save();
    }

    public function unArchive() {
        $this->archived_at = null;
        return $this->save();
    }

}
