<?php

namespace App\Policies;

use App\Post;
use App\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PostPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can report the post.
     *
     * @param User $user
     * @param Post $post
     * @return bool
     */
    public function report(User $user, Post $post)
    {
        return $post->reports()->where('user_id', $user->id)->doesntExist();
    }

    public function edit(User $user, Post $post)
    {
        return $post->user_id === $user->id;
    }


}
