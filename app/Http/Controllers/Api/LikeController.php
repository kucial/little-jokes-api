<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\PostLike;
use App\Http\Resources\PostLike as PostLikeResource;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    /**
     * Make like record archived
     * @param $id
     * @return PostLikeResource|int
     */
    public function archive($id) {
        $like = PostLike::findOrFail($id);
        $user = auth()->user();
        if ($user->can('archive', $like)) {
            if ($like->hasArchived()) {
                return response()->json([
                    'code' => 'HAS_ARCHIVED'
                ], 400);
            }
            $like->archive();

            return new PostLikeResource($like);
        } else {
            return response()->json([
                'code' => 'NOT_AUTHORIZED',
            ], 403);
        }
    }

    /**
     * Make like record unarchived
     *
     * @param $id
     * @return PostLikeResource|int
     */
    public function unArchive($id) {
        $like = PostLike::findOrFail($id);
        $user = auth()->user();
        if ($user->can('archive', $like)) {
            if (!$like->hasArchived()) {
                return response()->json([
                    'code' => 'NOT_ARCHIVED'
                ], 400);
            }
            $like->unArchive();

            return new PostLikeResource($like);
        } else {
            return response()->json([
                'code' => 'NOT_AUTHORIZED',
            ], 403);
        }
    }
}
