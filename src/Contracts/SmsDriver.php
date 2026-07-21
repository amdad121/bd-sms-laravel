<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Contracts;

interface SmsDriver
{
    /**
     * Send an SMS to a single recipient, returning whether the gateway accepted it.
     */
    public function send(string $to, string $message): bool;

    /**
     * Get the remaining prepaid balance on the gateway account, or null if unavailable.
     */
    public function balance(): ?float;
}
