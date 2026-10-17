<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Events;

use AmdadulHaq\BdSms\SmsResult;
use Throwable;

/**
 * Dispatched when the gateway rejects an SMS, carrying its result, or when
 * sending throws, carrying the exception instead.
 */
class SmsFailed
{
    public function __construct(
        public readonly string $driver,
        public readonly string $to,
        public readonly string $message,
        public readonly ?SmsResult $result = null,
        public readonly ?Throwable $exception = null,
    ) {}
}
