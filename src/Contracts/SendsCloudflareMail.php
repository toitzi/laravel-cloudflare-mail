<?php

namespace Toitzi\LaravelCloudflareMail\Contracts;

use Toitzi\LaravelCloudflareMail\CloudflareTransportConfig;

interface SendsCloudflareMail
{
    /**
     * Send a message payload through the Cloudflare Email Service API.
     *
     * @return array<string, mixed>
     */
    public function send(array $payload, CloudflareTransportConfig $config): array;
}
