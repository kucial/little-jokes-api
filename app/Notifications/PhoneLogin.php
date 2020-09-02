<?php

namespace App\Notifications;

use App\Channels\DySMSChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use NotificationChannels\Twilio\TwilioChannel;
use NotificationChannels\Twilio\TwilioSmsMessage;

class PhoneLogin extends Notification
{
    use Queueable;

    protected $code;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($code)
    {
        $this->code = $code;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = [];
        if (Arr::exists($notifiable->routes, 'dysms')) {
            $channels[] = DySMSChannel::class;
        }
        if (Arr::exists($notifiable->routes, 'twilio')) {
            $channels[] = TwilioChannel::class;
        }
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('The introduction to the notification.')
                    ->action('Notification Action', url('/'))
                    ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    public function toTwilio($notifiable)
    {
        return (new TwilioSmsMessage())
            ->content($this->getMessage());
    }

    public function toDySMS()
    {
        return [
            'template' => env('DYSMS_LOGIN_CODE_TEMPLATE'),
            'code' => $this->code
        ];
    }

    protected function getMessage() {
        $ttl = (int) env('LOGIN_CODE_TTL') / 60;
        return sprintf('您的登录验证码：%s，有效时间为 %d 分钟', $this->code, $ttl);
    }
}
