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
 * Driver for Alpha SMS (https://sms.net.bd).
 */
class AlphaSmsDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;
    use ReadsGatewayResponses;

    /**
     * Alpha SMS success response code.
     */
    private const int SUCCESS_CODE = 0;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly ?string $senderId = null,
        private readonly int $timeout = 10,
    ) {}

    public function send(string $to, string $message): SmsResult
    {
        $payload = [
            'api_key' => $this->apiKey,
            'msg' => $message,
            'to' => $this->normalizeNumber($to),
        ];

        if ($this->senderId !== null) {
            $payload['sender_id'] = $this->senderId;
        }

        $response = Http::timeout($this->timeout)->asForm()->post(Str::finish($this->baseUrl, '/').'sendsms', $payload);

        $body = $this->responseBody($response);
        $code = data_get($body, 'error');

        if (! is_numeric($code) || (int) $code !== self::SUCCESS_CODE) {
            return SmsResult::rejected(
                errorCode: $this->stringOrNull($code) ?? (string) $response->status(),
                errorMessage: $this->stringOrNull(data_get($body, 'msg')),
                response: $body,
            );
        }

        return SmsResult::accepted($this->stringOrNull(data_get($body, 'data.request_id')), $body);
    }

    public function balance(): ?float
    {
        try {
            $response = Http::timeout($this->timeout)->get(Str::finish($this->baseUrl, '/').'user/balance/', [
                'api_key' => $this->apiKey,
            ]);
        } catch (Throwable) {
            return null;
        }

        $balance = data_get($response->json(), 'data.balance');

        return is_numeric($balance) ? (float) $balance : null;
    }
}
