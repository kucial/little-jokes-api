<?php

namespace App\Console\Commands;

use App\Notifications\SMSNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendSMS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:send {phone} {message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send SMS message';

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
     * @return int
     */
    public function handle()
    {
        $phone = $this->argument('phone');
        $message = $this->argument('message');
        try {
            Notification::route('twilio', $phone)
                ->notify(new SMSNotification($message));
        } catch (\Exception $err) {
            $this->error($err->getMessage());
        }
    }
}
