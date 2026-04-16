<?php

namespace Toitzi\LaravelCloudflareMail;

use Toitzi\LaravelCloudflareMail\Contracts\SendsCloudflareMail;

final readonly class CloudflareTransportFactory
{
    public function __construct(
        private SendsCloudflareMail      $client,
        private CloudflarePayloadBuilder $payloadBuilder,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public function make(array $config = []): CloudflareTransport
    {
        return new CloudflareTransport(
            client: $this->client,
            payloadBuilder: $this->payloadBuilder,
            config: CloudflareTransportConfig::fromArray($config),
        );
    }
}
