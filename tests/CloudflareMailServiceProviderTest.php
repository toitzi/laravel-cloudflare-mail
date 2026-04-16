<?php

use Illuminate\Mail\Mailer;
use Toitzi\LaravelCloudflareMail\CloudflareTransport;

it('registers the cloudflare mail transport with laravel', function (): void {
    /** @var Mailer $mailer */
    $mailer = app('mail.manager')->mailer('cloudflare');

    expect($mailer->getSymfonyTransport())->toBeInstanceOf(CloudflareTransport::class);
});
