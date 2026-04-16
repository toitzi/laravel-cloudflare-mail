<?php

namespace Toitzi\LaravelCloudflareMail;

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Mime\MessageConverter;
use Toitzi\LaravelCloudflareMail\Contracts\SendsCloudflareMail;
use Toitzi\LaravelCloudflareMail\Exceptions\CloudflareTransportException;

final class CloudflareTransport extends AbstractTransport
{
    public function __construct(
        private readonly SendsCloudflareMail $client,
        private readonly CloudflarePayloadBuilder $payloadBuilder,
        private readonly CloudflareTransportConfig $config,
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = $this->toEmail($message);

        /** @noinspection UnusedFunctionResultInspection */
        $this->client->send(
            $this->payloadBuilder->build($email),
            $this->config,
        );
    }

    private function toEmail(SentMessage $message): Email
    {
        $original = $message->getOriginalMessage();

        if ($original instanceof Email) {
            return $original;
        }

        if ($original instanceof Message) {
            return MessageConverter::toEmail($original);
        }

        throw CloudflareTransportException::invalidMessage(
            sprintf(
                'Cloudflare transport only supports Symfony [%s] and [%s] messages. [%s] given.',
                Email::class,
                Message::class,
                $original::class,
            ),
        );
    }

    public function __toString(): string
    {
        return 'cloudflare';
    }
}
