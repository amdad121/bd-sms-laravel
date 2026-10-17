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
 * Driver for GreenWeb BD (https://greenweb.com.bd). Its send response carries
 * no message id.
 */
class GreenWebDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;
    use ReadsGatewayResponses;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
        private readonly int $timeout = 10,
    ) {}

    public function send(string $to, string $message): SmsResult
    {
        $response = Http::timeout($this->timeout)->asForm()->post(Str::finish($this->baseUrl, '/').'api.php', [
            'token' => $this->token,
            'to' => $this->normalizeNumber($to),
            'message' => $message,
            'json' => 1,
        ]);

        $body = $this->responseBody($response);
        $status = data_get($body, '0.status');

        if ($status !== 'SENT') {
            return SmsResult::rejected(
                errorCode: $this->stringOrNull($status) ?? (string) $response->status(),
                errorMessage: $this->stringOrNull(data_get($body, '0.statusmsg')),
                response: $body,
            );
        }

        return SmsResult::accepted(response: $body);
    }

    /**
     * GreenWeb BD's balance response field isn't documented publicly; this
     * reads the most commonly seen "balance" key and returns null otherwise.
     */
    public function balance(): ?float
    {
        try {
            $response = Http::timeout($this->timeout)->get(Str::finish($this->baseUrl, '/').'g_api.php', [
                'token' => $this->token,
                'balance' => '',
                'json' => 1,
            ]);
        } catch (Throwable) {
            return null;
        }

        $balance = data_get($response->json(), 'balance');

        return is_numeric($balance) ? (float) $balance : null;
    }
}
