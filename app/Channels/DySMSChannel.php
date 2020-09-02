<?php

namespace App\Channels;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DySMSChannel
{

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, Notification $notification)
    {

        $data = $notification->toDySMS($notifiable);
        $to = $this->getTo($notifiable);
        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $to,
                        'SignName' => env('DYSMS_SIGN_NAME'),
                        'TemplateCode' => $data['template'],
                        'TemplateParam' => json_encode([
                            'code' => $data['code']
                        ]),
                    ],
                ])
                ->request();
        } catch (ClientException $e) {
            Log::error($e->getErrorMessage());
        } catch (ServerException $e) {
            Log::error($e->getErrorMessage());
        }

    }

    protected function getTo($notifiable)
    {
        if ($notifiable->routeNotificationFor('dysms')) {
            return $notifiable->routeNotificationFor('dysms');
        }
        if (isset($notifiable->phone_number)) {
            return $notifiable->phone_number;
        }

    }
}