<?php

namespace App\Console\Commands;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class GenerateAppleClientSecret extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:apple-client-secret';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate apple client secret';

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
        try {
            $now = CarbonImmutable::now();
            $generator = Configuration::forSymmetricSigner(
                Sha256::create(),
                InMemory::file(storage_path(config('services.apple.private_key')))
            );
            $token = $generator->builder()
                ->issuedBy(config('services.apple.team_id'))
                ->issuedAt($now)
                ->expiresAt($now->addHour())
                ->permittedFor('https://appleid.apple.com')
                ->relatedTo(config('services.apple.client_id'))
                ->withHeader('kid', config('services.apple.key_id'))
                ->getToken($generator->signer(), $generator->signingKey());
            Storage::disk('local')->put('apple_secret', $token->toString());
            echo ('apple client secret updated.');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}
