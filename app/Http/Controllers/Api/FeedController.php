<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Post;
use App\Http\Resources\Post as PostResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FeedController extends Controller
{
    public function latest(Request $request)
    {
        $pageSize = (int) $request->query('page_size', 20);

        $userId = auth()->id();

        $posts = Post::with([
            'like' => function ($query) use($userId) {
                $query->where('post_likes.user_id', $userId);
            }
        ])->orderBy('created_at', 'desc')->paginate($pageSize);
        return PostResource::collection($posts);
    }

    public function random(Request $request)
    {
        $request->validate([
            'seed' => 'required|integer',
        ]);
        $pageSize = (int) $request->query('page_size', 20);

        $userId = auth()->id();
        $posts = Post::with([
            'like' => function ($query) use($userId) {
                $query->where('post_likes.user_id', $userId);
            }
        ])->inRandomOrder($request->query('seed'))->paginate($pageSize);

        return PostResource::collection($posts);
    }

    /**
     * Get Hottest Post
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function hottest(Request $request)
    {
        // 一段时间内的得分排序
        $pageSize = (int) $request->query('page_size', 20);
        $userId = auth()->id();
//        $time = Carbon::now()->subHour(12);

        $recentScores = DB::table('post_votes')
            ->select('post_id', DB::raw('SUM(vote_type) as score'))
//            ->where('updated_at', '>', $time)
            ->groupBy('post_id');

        $posts = Post::with([
            'like' => function ($query) use($userId) {
                $query->where('post_likes.user_id', $userId);
            }
        ])->leftJoinSub($recentScores, 'post_scores', function($join) {
            $join->on('posts.id', '=', 'post_scores.post_id');
        })->select(
            ['posts.*', 'post_scores.score']
        )->orderBy('post_scores.score', 'desc')->paginate($pageSize);
        return PostResource::collection($posts);
    }
}
