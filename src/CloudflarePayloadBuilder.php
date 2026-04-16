<?php

namespace Toitzi\LaravelCloudflareMail;

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Part\DataPart;
use Toitzi\LaravelCloudflareMail\Exceptions\CloudflareTransportException;

final class CloudflarePayloadBuilder
{
    /**
     * @var array<int, string>
     */
    private array $reservedHeaders = [
        'bcc',
        'cc',
        'content-disposition',
        'content-id',
        'content-transfer-encoding',
        'content-type',
        'date',
        'from',
        'message-id',
        'mime-version',
        'reply-to',
        'return-path',
        'sender',
        'subject',
        'to',
    ];

    /**
     * @return array<string, mixed>
     */
    public function build(Email $email): array
    {
        $payload = [
            'from' => $this->normalizeSingleAddress($email->getFrom(), 'from'),
            'to' => $this->normalizeAddressList($email->getTo(), 'to'),
            'subject' => (string) $email->getSubject(),
        ];

        $html = $email->getHtmlBody();
        $text = $email->getTextBody();

        if ($html === null && $text === null) {
            throw CloudflareTransportException::invalidMessage('Cloudflare requires at least one HTML or text body.');
        }

        if ($html !== null) {
            $payload['html'] = $html;
        }

        if ($text !== null) {
            $payload['text'] = $text;
        }

        $cc = $this->normalizeOptionalAddressList($email->getCc());

        if ($cc !== []) {
            $payload['cc'] = $cc;
        }

        $bcc = $this->normalizeOptionalAddressList($email->getBcc());

        if ($bcc !== []) {
            $payload['bcc'] = $bcc;
        }

        $replyTo = $email->getReplyTo();

        if ($replyTo !== []) {
            $payload['reply_to'] = $this->normalizeSingleAddress($replyTo, 'reply_to');
        }

        $attachments = $this->buildAttachments($email->getAttachments());

        if ($attachments !== []) {
            $payload['attachments'] = $attachments;
        }

        $headers = $this->buildHeaders($email);

        if ($headers !== []) {
            $payload['headers'] = $headers;
        }

        return $payload;
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<string, string>|string
     */
    private function normalizeSingleAddress(array $addresses, string $field): array|string
    {
        if ($addresses === []) {
            throw CloudflareTransportException::invalidMessage(sprintf('Cloudflare requires a [%s] address.', $field));
        }

        if (count($addresses) > 1) {
            throw CloudflareTransportException::invalidMessage(sprintf('Cloudflare only supports a single [%s] address.', $field));
        }

        return $this->normalizeAddress(array_values($addresses)[0]);
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, string>
     */
    private function normalizeAddressList(array $addresses, string $field): array
    {
        if ($addresses === []) {
            throw CloudflareTransportException::invalidMessage(sprintf('Cloudflare requires at least one [%s] recipient.', $field));
        }

        return $this->normalizeOptionalAddressList($addresses);
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, string>
     */
    private function normalizeOptionalAddressList(array $addresses): array
    {
        return array_map(
            static fn (Address $address): string => $address->getAddress(),
            array_values($addresses),
        );
    }

    /**
     * @return array<string, string>|string
     */
    private function normalizeAddress(Address $address): array|string
    {
        if ($address->getName() === '') {
            return $address->getAddress();
        }

        return [
            'address' => $address->getAddress(),
            'name' => $address->getName(),
        ];
    }

    /**
     * @param  array<int, DataPart>  $attachments
     * @return array<int, array<string, string>>
     */
    private function buildAttachments(array $attachments): array
    {
        $normalized = [];

        foreach (array_values($attachments) as $index => $attachment) {
            $normalized[] = $this->normalizeAttachment($attachment, $index);
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private function normalizeAttachment(DataPart $attachment, int $index): array
    {
        if ($attachment->getDisposition() !== 'attachment') {
            throw CloudflareTransportException::invalidMessage(
                'Cloudflare only supports attachments with an [attachment] disposition. Inline attachments are not supported.',
            );
        }

        return [
            // Cloudflare expects raw attachment bytes base64-encoded once.
            'content' => base64_encode($attachment->getBody()),
            'disposition' => 'attachment',
            'filename' => $attachment->getFilename() ?? sprintf('attachment-%d', $index + 1),
            'type' => $this->contentType($attachment),
        ];
    }

    private function contentType(DataPart $attachment): string
    {
        $mediaType = trim($attachment->getMediaType());
        $mediaSubtype = trim($attachment->getMediaSubtype());

        if ($mediaType === '' || $mediaSubtype === '') {
            return 'application/octet-stream';
        }

        return sprintf('%s/%s', $mediaType, $mediaSubtype);
    }

    /**
     * @return array<string, string>
     */
    private function buildHeaders(Email $email): array
    {
        $headers = [];

        foreach ($email->getHeaders()->all() as $header) {
            if (! $header instanceof HeaderInterface) {
                continue;
            }

            $name = $header->getName();

            if ($this->isReservedHeader($name)) {
                continue;
            }

            $headers[$name] = trim($header->getBodyAsString());
        }

        return $headers;
    }

    private function isReservedHeader(string $name): bool
    {
        return in_array(strtolower($name), $this->reservedHeaders, true);
    }
}
