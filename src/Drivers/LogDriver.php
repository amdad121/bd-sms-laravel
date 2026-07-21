<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use Illuminate\Support\Facades\Log;

/**
 * Fallback driver that writes messages to the log instead of dispatching them.
 * Useful for local development and as a safe default when no gateway is configured.
 */
class LogDriver implements SmsDriver
{
    public function send(string $to, string $message): bool
    {
        Log::info('SMS (not sent — log driver).', [
            'to' => $to,
            'message' => $message,
        ]);

        return true;
    }

    public function balance(): ?float
    {
        return null;
    }
}
