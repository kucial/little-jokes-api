<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class T2SUpdate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'post:t2s';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update post content from traditional to simplified';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $opencc = app()->make('opencc');
        \App\Post::chunk(200, function ($posts) use($opencc) {
            foreach($posts as $post) {
                $s = $opencc->transform($post->content, 't2s.json');
                if($s && $s !== $post->content) {
                    $this->info('update', $post->id, $s);
                }
                $post->content = $s;
                $post->timestamps = false;
                $post->save();
            }
        });
    }
}
