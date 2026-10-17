<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Contracts;

use AmdadulHaq\BdSms\SmsResult;

interface SmsDriver
{
    /**
     * Send an SMS to a single recipient. A gateway rejection is returned as a
     * failed result; only transport errors (e.g. a timeout) are thrown.
     */
    public function send(string $to, string $message): SmsResult;

    /**
     * Get the remaining prepaid balance on the gateway account, or null if unavailable.
     */
    public function balance(): ?float;
}
