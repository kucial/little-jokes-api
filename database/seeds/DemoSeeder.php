<?php

use Illuminate\Database\Seeder;

class DemoSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $rawStr = file_get_contents(__DIR__. '/demo.json');
        $data = json_decode($rawStr, true);
        \App\User::truncate();
        // user
        factory(\App\User::class, 20)->create();

        // jokes
        \App\Post::truncate();
        foreach ($data['jokes'] as $item) {
            $post = new \App\Post();
            $post->user_id = 1;
            $post->content = $item;
            $post->save();
        }
    }
}
