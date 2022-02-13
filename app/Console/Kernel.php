<?php

namespace App\Console;

use App\Console\Commands\SendLoginSMS;
use App\Console\Commands\SendRegisterSMS;
use App\Console\Commands\SendSMS;
use App\Console\Commands\T2SUpdate;
use App\Console\Commands\DbBackup;
use App\Console\Commands\GenerateAppleClientSecret;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        SendSMS::class,
        SendRegisterSMS::class,
        SendLoginSMS::class,
        T2SUpdate::class,
        GenerateAppleClientSecret::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('db:backup')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
