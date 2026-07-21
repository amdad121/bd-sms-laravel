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
 * Driver for BulkSMSBD (https://bulksmsbd.net).
 */
class BulkSmsBdDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;

    /**
     * BulkSMSBD success response code (202 — SMS Submitted Successfully).
     */
    private const SUCCESS_CODE = 202;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $senderId,
    ) {}

    public function send(string $to, string $message): bool
    {
        $response = Http::asForm()->post(Str::finish($this->baseUrl, '/').'api/smsapi', [
            'api_key' => $this->apiKey,
            'type' => 'text',
            'senderid' => $this->senderId,
            'number' => $this->normalizeNumber($to),
            'message' => $message,
        ]);

        $code = (int) data_get($response->json(), 'response_code');

        if ($code !== self::SUCCESS_CODE) {
            Log::warning('BulkSMSBD rejected an SMS.', [
                'response_code' => $code,
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * BulkSMSBD's docs confirm this endpoint but don't publish an example
     * JSON body; this reads the conventional "balance" key.
     */
    public function balance(): ?float
    {
        try {
            $response = Http::get(Str::finish($this->baseUrl, '/').'api/getBalanceApi', [
                'api_key' => $this->apiKey,
            ]);

            $balance = data_get($response->json(), 'balance');

            return is_numeric($balance) ? (float) $balance : null;
        } catch (Throwable $throwable) {
            Log::warning('BulkSMSBD balance check failed.', ['message' => $throwable->getMessage()]);

            return null;
        }
    }
}
