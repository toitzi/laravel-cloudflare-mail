<?php

use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Toitzi\LaravelCloudflareMail\CloudflarePayloadBuilder;
use Toitzi\LaravelCloudflareMail\CloudflareTransport;
use Toitzi\LaravelCloudflareMail\CloudflareTransportConfig;
use Toitzi\LaravelCloudflareMail\Contracts\SendsCloudflareMail;
use Toitzi\LaravelCloudflareMail\Exceptions\CloudflareTransportException;

it('sends the cloudflare api payload built from a symfony email', function (): void {
    Http::fake([
        'https://api.cloudflare.com/*' => Http::response([
            'success' => true,
            'errors' => [],
            'messages' => [],
            'result' => [
                'delivered' => ['user1@example.com', 'user2@example.com'],
                'permanent_bounces' => [],
                'queued' => [],
            ],
        ]),
    ]);

    $transport = new CloudflareTransport(
        client: app(SendsCloudflareMail::class),
        payloadBuilder: app(CloudflarePayloadBuilder::class),
        config: CloudflareTransportConfig::fromArray([
            'account_id' => 'test-account-id',
            'api_token' => 'test-api-token',
            'base_url' => 'https://api.cloudflare.com/client/v4',
            'timeout' => 30,
            'connect_timeout' => 10,
        ]),
    );

    $email = (new Email())
        ->from(new Address('newsletter@example.com', 'Newsletter Team'))
        ->to(
            new Address('user1@example.com', 'User One'),
            new Address('user2@example.com', 'User Two'),
        )
        ->cc('manager@example.com')
        ->bcc('archive@example.com')
        ->replyTo(new Address('support@example.com', 'Support'))
        ->subject('Monthly Newsletter')
        ->html('<h1>This month&apos;s updates</h1>')
        ->text("This month's updates")
        ->attach('Attachment body', 'invoice.txt', 'text/plain');

    $email->getHeaders()->addTextHeader('X-Campaign-ID', 'monthly-newsletter');
    $email->getHeaders()->addTextHeader('In-Reply-To', '<thread@example.com>');

    $transport->send($email);

    Http::assertSent(function (Request $request): bool {
        expect($request->method())->toBe('POST')
            ->and($request->url())->toBe('https://api.cloudflare.com/client/v4/accounts/test-account-id/email/sending/send')
            ->and($request->hasHeader('Authorization', 'Bearer test-api-token'))->toBeTrue()
            ->and($request['from'])->toBe([
                'address' => 'newsletter@example.com',
                'name' => 'Newsletter Team',
            ])
            ->and($request['to'])->toBe([
                'user1@example.com',
                'user2@example.com',
            ])
            ->and($request['cc'])->toBe([
                'manager@example.com',
            ])
            ->and($request['bcc'])->toBe([
                'archive@example.com',
            ])
            ->and($request['reply_to'])->toBe([
                'address' => 'support@example.com',
                'name' => 'Support',
            ])
            ->and($request['subject'])->toBe('Monthly Newsletter')
            ->and($request['html'])->toBe('<h1>This month&apos;s updates</h1>')
            ->and($request['text'])->toBe("This month's updates")
            ->and($request['headers'])->toBe([
                'X-Campaign-ID' => 'monthly-newsletter',
                'In-Reply-To' => '<thread@example.com>',
            ])
            ->and($request['attachments'])->toHaveCount(1)
            ->and($request['attachments'][0])->toMatchArray([
                'content' => base64_encode('Attachment body'),
                'disposition' => 'attachment',
                'filename' => 'invoice.txt',
                'type' => 'text/plain',
            ]);

        return true;
    });
});

it('throws a helpful exception when cloudflare returns an api error', function (): void {
    Http::fake([
        'https://api.cloudflare.com/*' => Http::response([
            'success' => false,
            'errors' => [
                [
                    'code' => 10001,
                    'message' => 'email.sending.error.invalid_request_schema',
                ],
            ],
            'messages' => [],
            'result' => null,
        ], 400),
    ]);

    $transport = new CloudflareTransport(
        client: app(SendsCloudflareMail::class),
        payloadBuilder: app(CloudflarePayloadBuilder::class),
        config: CloudflareTransportConfig::fromArray([
            'account_id' => 'test-account-id',
            'api_token' => 'test-api-token',
            'base_url' => 'https://api.cloudflare.com/client/v4',
        ]),
    );

    $email = (new Email())
        ->from('newsletter@example.com')
        ->to('user@example.com')
        ->subject('Invalid request')
        ->text('Body');

    expect(fn () => $transport->send($email))
        ->toThrow(CloudflareTransportException::class, 'email.sending.error.invalid_request_schema');
});

it('rejects inline attachments because the cloudflare rest api does not support them', function (): void {
    $transport = new CloudflareTransport(
        client: app(SendsCloudflareMail::class),
        payloadBuilder: app(CloudflarePayloadBuilder::class),
        config: CloudflareTransportConfig::fromArray([
            'account_id' => 'test-account-id',
            'api_token' => 'test-api-token',
            'base_url' => 'https://api.cloudflare.com/client/v4',
        ]),
    );

    $email = (new Email())
        ->from('newsletter@example.com')
        ->to('user@example.com')
        ->subject('Inline attachment')
        ->html('<img src="cid:logo">')
        ->text('Inline attachment');

    $email->embed('image-bytes', 'logo.png', 'image/png');

    expect(static fn () => $transport->send($email))
        ->toThrow(CloudflareTransportException::class, 'Inline attachments are not supported');
});

it('requires cloudflare credentials when building a transport configuration', function (): void {
    expect(static fn (): CloudflareTransportConfig => CloudflareTransportConfig::fromArray([]))
        ->toThrow(CloudflareTransportException::class, 'account_id');
});
