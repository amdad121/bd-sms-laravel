<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Drivers\Concerns\NormalizesBangladeshiNumbers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Driver for GreenWeb BD (https://greenweb.com.bd).
 */
class GreenWebDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
    ) {}

    public function send(string $to, string $message): bool
    {
        $response = Http::asForm()->post(Str::finish($this->baseUrl, '/').'api.php', [
            'token' => $this->token,
            'to' => $this->normalizeNumber($to),
            'message' => $message,
            'json' => 1,
        ]);

        $status = data_get($response->json(), '0.status');

        if ($status !== 'SENT') {
            Log::warning('GreenWeb BD rejected an SMS.', [
                'status' => $status,
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * GreenWeb BD's balance response field isn't documented publicly; this
     * reads the most commonly seen "balance" key and returns null otherwise.
     */
    public function balance(): ?float
    {
        try {
            $response = Http::get(Str::finish($this->baseUrl, '/').'g_api.php', [
                'token' => $this->token,
                'balance' => '',
                'json' => 1,
            ]);

            $balance = data_get($response->json(), 'balance');

            return is_numeric($balance) ? (float) $balance : null;
        } catch (Throwable $throwable) {
            Log::warning('GreenWeb BD balance check failed.', ['message' => $throwable->getMessage()]);

            return null;
        }
    }
}
