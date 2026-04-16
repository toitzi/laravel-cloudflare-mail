<?php

namespace Toitzi\LaravelCloudflareMail\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Toitzi\LaravelCloudflareMail\CloudflareMailServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CloudflareMailServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('mail.default', 'cloudflare');
        $app['config']->set('mail.mailers.cloudflare', [
            'transport' => 'cloudflare',
            'account_id' => 'package-account-id',
            'api_token' => 'package-api-token',
            'base_url' => 'https://api.cloudflare.com/client/v4',
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
    }
}
