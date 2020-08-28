<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UserSeeder::class);

        factory(\App\User::class, 20)->create();
        factory(\App\Post::class, 50)->create()->each(function($post) {
            $likesCount = mt_rand(0, 20);
            if ($likesCount) {
                \Illuminate\Support\Facades\DB::table('users')
                ->select('id')->inRandomOrder()->get($likesCount)
                ->each(function($item) use ($post) {
                    $like = new \App\PostLike();
                    $like->user_id = $item->id;
                    $like->post_id = $post->id;
                    $like->save();
                    if (mt_rand(0, 9) > 6) {
                        $like->archive();
                    }
                });
            }
        });
    }
}
