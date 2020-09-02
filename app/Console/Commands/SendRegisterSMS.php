<?php

namespace App\Console\Commands;

use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Illuminate\Console\Command;

class SendRegisterSMS extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sms:send-register-code {phone} {code}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send register code SMS';

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
        $phone = $this->argument('phone');
        $code = $this->argument('code');

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $phone,
                        'SignName' => env('DYSMS_SIGN_NAME'),
                        'TemplateCode' => env('DYSMS_REGISTER_CODE_TEMPLATE'),
                        'TemplateParam' => json_encode([
                            'code' => $code,
                        ]),
                    ],
                ])
                ->request();
            print_r($result->toArray());
        } catch (ClientException $e) {
            $this->error($e->getErrorMessage());
        } catch (ServerException $e) {
            $this->error($e->getErrorMessage());
        }
    }
}
