<?php

namespace Toitzi\LaravelCloudflareMail;

use Toitzi\LaravelCloudflareMail\Exceptions\CloudflareTransportException;

final readonly class CloudflareTransportConfig
{
    public function __construct(
        public string $accountId,
        public string $apiToken,
        public string $baseUrl,
        public float  $timeout,
        public float  $connectTimeout,
    ) {
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromArray(array $config): self
    {
        $accountId = trim((string) ($config['account_id'] ?? ''));

        if ($accountId === '') {
            throw CloudflareTransportException::missingConfiguration('account_id');
        }

        $apiToken = trim((string) ($config['api_token'] ?? $config['token'] ?? ''));

        if ($apiToken === '') {
            throw CloudflareTransportException::missingConfiguration('api_token');
        }

        $baseUrl = trim((string) ($config['base_url'] ?? $config['endpoint'] ?? 'https://api.cloudflare.com/client/v4'));

        if ($baseUrl === '') {
            throw CloudflareTransportException::missingConfiguration('base_url');
        }

        return new self(
            accountId: $accountId,
            apiToken: $apiToken,
            baseUrl: rtrim($baseUrl, '/'),
            timeout: self::float($config, 'timeout', 30),
            connectTimeout: self::float($config, 'connect_timeout', 10),
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private static function float(array $config, string $key, float $default): float
    {
        $value = $config[$key] ?? $default;

        if (! is_numeric($value)) {
            throw CloudflareTransportException::invalidMessage(sprintf('Cloudflare mail configuration value [%s] must be numeric.', $key));
        }

        return (float) $value;
    }
}
