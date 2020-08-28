<?php

namespace App\Policies;

use App\User;
use App\PostLike;
use Illuminate\Auth\Access\HandlesAuthorization;

class LikePolicy
{
    use HandlesAuthorization;

    /**
     * Create a new policy instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determin whether the use can archive/unarchive the post like.
     *
     * @param User $user
     * @param PostLike $like
     * @return bool
     */
    public function archive(User $user, PostLike $like)
    {
        return $like->user_id === $user->id;
    }
}
