<?php

namespace Toitzi\LaravelCloudflareMail\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class CloudflareTransportException extends RuntimeException
{
    public static function missingConfiguration(string $key): self
    {
        return new self(sprintf('Missing Cloudflare mail configuration value [%s].', $key));
    }

    public static function invalidMessage(string $reason): self
    {
        return new self($reason);
    }

    public static function fromResponse(Response $response): self
    {
        $status = $response->status();
        $payload = $response->json();

        if (! is_array($payload)) {
            return new self(sprintf('Cloudflare mail API request failed with status [%d].', $status), $status);
        }

        $errors = $payload['errors'] ?? [];

        if (! is_array($errors) || $errors === []) {
            return new self(sprintf('Cloudflare mail API request failed with status [%d].', $status), $status);
        }

        $details = collect($errors)
            ->filter(fn (mixed $error): bool => is_array($error))
            ->map(function (array $error): string {
                $code = $error['code'] ?? 'unknown';
                $message = $error['message'] ?? 'Unknown Cloudflare API error.';

                return sprintf('[%s] %s', $code, $message);
            })
            ->implode('; ');

        return new self(
            sprintf('Cloudflare mail API request failed with status [%d]: %s', $status, $details),
            $status,
        );
    }

    public static function unexpectedResponse(Response $response): self
    {
        return new self(
            sprintf('Cloudflare mail API returned an unexpected response with status [%d].', $response->status()),
            $response->status(),
        );
    }
}
