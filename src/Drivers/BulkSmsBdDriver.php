<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Drivers\Concerns\NormalizesBangladeshiNumbers;
use AmdadulHaq\BdSms\Drivers\Concerns\ReadsGatewayResponses;
use AmdadulHaq\BdSms\SmsResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Driver for BulkSMSBD (https://bulksmsbd.net).
 */
class BulkSmsBdDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;
    use ReadsGatewayResponses;

    /**
     * BulkSMSBD success response code (202 — SMS Submitted Successfully).
     */
    private const int SUCCESS_CODE = 202;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $senderId,
        private readonly int $timeout = 10,
    ) {}

    public function send(string $to, string $message): SmsResult
    {
        $response = Http::timeout($this->timeout)->asForm()->post(Str::finish($this->baseUrl, '/').'api/smsapi', [
            'api_key' => $this->apiKey,
            'type' => 'text',
            'senderid' => $this->senderId,
            'number' => $this->normalizeNumber($to),
            'message' => $message,
        ]);

        $body = $this->responseBody($response);
        $code = (int) data_get($body, 'response_code');

        if ($code !== self::SUCCESS_CODE) {
            return SmsResult::rejected(
                errorCode: $code !== 0 ? (string) $code : (string) $response->status(),
                errorMessage: $this->stringOrNull(data_get($body, 'error_message')),
                response: $body,
            );
        }

        return SmsResult::accepted($this->stringOrNull(data_get($body, 'message_id')), $body);
    }

    /**
     * BulkSMSBD's docs confirm this endpoint but don't publish an example
     * JSON body; this reads the conventional "balance" key.
     */
    public function balance(): ?float
    {
        try {
            $response = Http::timeout($this->timeout)->get(Str::finish($this->baseUrl, '/').'api/getBalanceApi', [
                'api_key' => $this->apiKey,
            ]);
        } catch (Throwable) {
            return null;
        }

        $balance = data_get($response->json(), 'balance');

        return is_numeric($balance) ? (float) $balance : null;
    }
}
