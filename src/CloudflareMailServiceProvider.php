<?php

namespace Toitzi\LaravelCloudflareMail;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use Toitzi\LaravelCloudflareMail\Contracts\SendsCloudflareMail;

final class CloudflareMailServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SendsCloudflareMail::class, CloudflareApiClient::class);
        $this->app->singleton(CloudflarePayloadBuilder::class);
        $this->app->singleton(CloudflareTransportFactory::class);
    }

    public function boot(): void
    {
        /** @noinspection UnusedFunctionResultInspection */
        Mail::extend('cloudflare', function (array $config = []): CloudflareTransport {
            return $this->app->make(CloudflareTransportFactory::class)->make($config);
        });
    }
}
