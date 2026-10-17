<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms;

/**
 * What the gateway said about one SMS. The message id and error details are
 * read from each gateway's documented response and are null when the
 * gateway does not return them.
 */
final readonly class SmsResult
{
    /**
     * @param  array<mixed>|string|null  $response  the decoded JSON body, or the raw body when it is not JSON
     */
    public function __construct(
        public bool $accepted,
        public ?string $messageId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
        public array|string|null $response = null,
    ) {}

    /**
     * @param  array<mixed>|string|null  $response
     */
    public static function accepted(?string $messageId = null, array|string|null $response = null): self
    {
        return new self(true, $messageId, response: $response);
    }

    /**
     * @param  array<mixed>|string|null  $response
     */
    public static function rejected(?string $errorCode = null, ?string $errorMessage = null, array|string|null $response = null): self
    {
        return new self(false, errorCode: $errorCode, errorMessage: $errorMessage, response: $response);
    }

    public function failed(): bool
    {
        return ! $this->accepted;
    }
}
