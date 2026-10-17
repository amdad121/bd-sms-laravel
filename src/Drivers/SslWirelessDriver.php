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
 * Driver for SSL Wireless SMS Plus (https://sslwireless.com).
 */
class SslWirelessDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;
    use ReadsGatewayResponses;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiToken,
        private readonly string $sid,
        private readonly int $timeout = 10,
    ) {}

    public function send(string $to, string $message): SmsResult
    {
        $csmsId = (string) Str::uuid();

        $response = Http::timeout($this->timeout)->asJson()->post(Str::finish($this->baseUrl, '/').'api/v3/send-sms', [
            'api_token' => $this->apiToken,
            'sid' => $this->sid,
            'msisdn' => $this->normalizeNumber($to),
            'sms' => $message,
            'csms_id' => $csmsId,
        ]);

        $body = $this->responseBody($response);

        if (! Str::contains((string) data_get($body, 'status', ''), 'SUCCESS', ignoreCase: true)) {
            return SmsResult::rejected(
                errorCode: $this->stringOrNull(data_get($body, 'status_code')) ?? (string) $response->status(),
                errorMessage: $this->stringOrNull(data_get($body, 'error_message')),
                response: $body,
            );
        }

        return SmsResult::accepted($this->stringOrNull(data_get($body, 'smsinfo.0.reference_id')) ?? $csmsId, $body);
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
            $response = Http::timeout($this->timeout)->get(Str::finish($this->baseUrl, '/').'api/balance', [
                'api_token' => $this->apiToken,
                'sid' => $this->sid,
            ]);
        } catch (Throwable) {
            return null;
        }

        $balance = data_get($response->json(), 'balance');

        return is_numeric($balance) ? (float) $balance : null;
    }
}
