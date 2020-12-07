<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;

class DbBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup';
/**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Database Backup';
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
        $folder =  storage_path() . "/app/backup/";
        if (!file_exists($folder)) {
            mkdir($folder, 0777);
        }

        $filename = "backup-" . Carbon::now()->format('Y-m-d') . ".gz";
        $command = "mysqldump --opt --databases ".env('DB_DATABASE')." -h ".env('DB_HOST')." -u " . env('DB_USERNAME') ." -p'" . env('DB_PASSWORD') . "' | gzip > " . $folder . $filename;
        $returnVar = NULL;
        $output  = NULL;
        exec($command, $output, $returnVar);
    }
}
