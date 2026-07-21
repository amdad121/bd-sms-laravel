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
 * Driver for MimSMS (https://mimsms.com).
 */
class MimSmsDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $apiKey,
        private readonly string $senderId,
    ) {}

    public function send(string $to, string $message): bool
    {
        $response = Http::asJson()->post(Str::finish($this->baseUrl, '/').'api/SmsSending/SMS', [
            'UserName' => $this->username,
            'Apikey' => $this->apiKey,
            'MobileNumber' => $this->normalizeNumber($to),
            'CampaignId' => '',
            'SenderName' => $this->senderId,
            'TransactionType' => 'T',
            'Message' => $message,
        ]);

        $success = $response->successful()
            && Str::contains((string) data_get($response->json(), 'status', ''), 'Success', ignoreCase: true);

        if (! $success) {
            Log::warning('MimSMS rejected an SMS.', ['body' => $response->body()]);
        }

        return $success;
    }

    public function balance(): ?float
    {
        try {
            $response = Http::asJson()->post(Str::finish($this->baseUrl, '/').'api/SmsSending/balanceCheck', [
                'UserName' => $this->username,
                'Apikey' => $this->apiKey,
            ]);

            $balance = data_get($response->json(), 'responseResult');

            return is_numeric($balance) ? (float) $balance : null;
        } catch (Throwable $throwable) {
            Log::warning('MimSMS balance check failed.', ['message' => $throwable->getMessage()]);

            return null;
        }
    }
}
