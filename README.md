# Laravel Cloudflare Mail

`toitzi/laravel-cloudflare-mail` adds a `cloudflare` mail transport to Laravel so mailers can send through the [Cloudflare Email Service REST API](https://developers.cloudflare.com/email-service/).

## Installation

```bash
composer require toitzi/laravel-cloudflare-mail
```

Laravel discovers the package automatically.

## Configuration

Define the mailer directly in `config/mail.php`:

```php
'default' => env('MAIL_MAILER', 'cloudflare'),

'mailers' => [
    'cloudflare' => [
        'transport' => 'cloudflare',
        'account_id' => env('CLOUDFLARE_EMAIL_ACCOUNT_ID'),
        'api_token' => env('CLOUDFLARE_EMAIL_API_TOKEN'),
        'base_url' => env('CLOUDFLARE_EMAIL_BASE_URL', 'https://api.cloudflare.com/client/v4'),
        'timeout' => 30,
        'connect_timeout' => 10,
    ],
],
```

## Supported message features

- HTML and plain text bodies
- `from`, `to`, `cc`, `bcc`, and single `reply-to` addresses
- Standard attachments
- Custom headers such as `In-Reply-To`, `References`, `List-Unsubscribe`, and `X-*`

## Limitations

- Cloudflare's REST API only supports a single `from` address.
- Cloudflare's REST API only supports a single `reply_to` address.
- Inline attachments are not supported by the REST API and will throw an exception.

## Testing

```bash
composer test
```
