<?php

namespace Toitzi\LaravelCloudflareMail;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory;
use Toitzi\LaravelCloudflareMail\Contracts\SendsCloudflareMail;
use Toitzi\LaravelCloudflareMail\Exceptions\CloudflareTransportException;

final readonly class CloudflareApiClient implements SendsCloudflareMail
{
    public function __construct(
        private Factory $http,
    ) {
    }

    /**
     * @throws ConnectionException
     */
    public function send(array $payload, CloudflareTransportConfig $config): array
    {
        $response = $this->http
            ->baseUrl($config->baseUrl)
            ->acceptJson()
            ->asJson()
            ->withToken($config->apiToken)
            ->timeout($config->timeout)
            ->connectTimeout($config->connectTimeout)
            ->post("/accounts/$config->accountId/email/sending/send", $payload);

        if ($response->failed()) {
            throw CloudflareTransportException::fromResponse($response);
        }

        $body = $response->json();

        if (! is_array($body) || ($body['success'] ?? false) !== true) {
            throw CloudflareTransportException::unexpectedResponse($response);
        }

        return $body;
    }
}
