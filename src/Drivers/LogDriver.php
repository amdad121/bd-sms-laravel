<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\SmsResult;
use Illuminate\Support\Facades\Log;

/**
 * Fallback driver that writes messages to the log instead of dispatching them.
 * Useful for local development and as a safe default when no gateway is configured.
 */
class LogDriver implements SmsDriver
{
    public function send(string $to, string $message): SmsResult
    {
        Log::info('SMS (not sent — log driver).', [
            'to' => $to,
            'message' => $message,
        ]);

        return SmsResult::accepted();
    }

    public function balance(): ?float
    {
        return null;
    }
}
