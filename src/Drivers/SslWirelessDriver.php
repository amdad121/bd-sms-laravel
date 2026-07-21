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
 * Driver for SSL Wireless SMS Plus (https://sslwireless.com).
 */
class SslWirelessDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiToken,
        private readonly string $sid,
    ) {}

    public function send(string $to, string $message): bool
    {
        $response = Http::asJson()->post(Str::finish($this->baseUrl, '/').'api/v3/send-sms', [
            'api_token' => $this->apiToken,
            'sid' => $this->sid,
            'msisdn' => $this->normalizeNumber($to),
            'sms' => $message,
            'csms_id' => (string) Str::uuid(),
        ]);

        $success = Str::contains((string) data_get($response->json(), 'status', ''), 'SUCCESS', ignoreCase: true);

        if (! $success) {
            Log::warning('SSL Wireless rejected an SMS.', ['body' => $response->body()]);
        }

        return $success;
    }

    /**
     * SSL Wireless doesn't publicly document a balance endpoint for the v3
     * API; this targets the conventional "api/balance" path and "balance"
     * response key seen on other BD gateways, but hasn't been confirmed
     * against SSL Wireless's own docs.
     */
    public function balance(): ?float
    {
        try {
            $response = Http::get(Str::finish($this->baseUrl, '/').'api/balance', [
                'api_token' => $this->apiToken,
                'sid' => $this->sid,
            ]);

            $balance = data_get($response->json(), 'balance');

            return is_numeric($balance) ? (float) $balance : null;
        } catch (Throwable $throwable) {
            Log::warning('SSL Wireless balance check failed.', ['message' => $throwable->getMessage()]);

            return null;
        }
    }
}
