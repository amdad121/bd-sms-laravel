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
 * Driver for Alpha SMS (https://sms.net.bd).
 */
class AlphaSmsDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;

    /**
     * Alpha SMS success response code.
     */
    private const SUCCESS_CODE = 0;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly ?string $senderId = null,
    ) {}

    public function send(string $to, string $message): bool
    {
        $payload = [
            'api_key' => $this->apiKey,
            'msg' => $message,
            'to' => $this->normalizeNumber($to),
        ];

        if ($this->senderId !== null) {
            $payload['sender_id'] = $this->senderId;
        }

        $response = Http::asForm()->post(Str::finish($this->baseUrl, '/').'sendsms', $payload);

        $code = (int) data_get($response->json(), 'error');

        if ($code !== self::SUCCESS_CODE) {
            Log::warning('Alpha SMS rejected an SMS.', [
                'error' => $code,
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    public function balance(): ?float
    {
        try {
            $response = Http::get(Str::finish($this->baseUrl, '/').'user/balance/', [
                'api_key' => $this->apiKey,
            ]);

            $balance = data_get($response->json(), 'data.balance');

            return is_numeric($balance) ? (float) $balance : null;
        } catch (Throwable $throwable) {
            Log::warning('Alpha SMS balance check failed.', ['message' => $throwable->getMessage()]);

            return null;
        }
    }
}
