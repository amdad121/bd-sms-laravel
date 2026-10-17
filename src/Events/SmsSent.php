<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Events;

use AmdadulHaq\BdSms\SmsResult;
use AmdadulHaq\BdSms\Support\SmsSegments;

/**
 * Dispatched after the gateway accepts an SMS.
 */
class SmsSent
{
    public function __construct(
        public readonly string $driver,
        public readonly string $to,
        public readonly string $message,
        public readonly SmsResult $result,
    ) {}

    public function segments(): int
    {
        return SmsSegments::count($this->message);
    }
}
