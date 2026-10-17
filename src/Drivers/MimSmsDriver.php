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
 * Driver for MimSMS (https://mimsms.com).
 */
class MimSmsDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;
    use ReadsGatewayResponses;

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $username,
        private readonly string $apiKey,
        private readonly string $senderId,
        private readonly int $timeout = 10,
    ) {}

    public function send(string $to, string $message): SmsResult
    {
        $response = Http::timeout($this->timeout)->asJson()->post(Str::finish($this->baseUrl, '/').'api/SmsSending/SMS', [
            'UserName' => $this->username,
            'Apikey' => $this->apiKey,
            'MobileNumber' => $this->normalizeNumber($to),
            'CampaignId' => '',
            'SenderName' => $this->senderId,
            'TransactionType' => 'T',
            'Message' => $message,
        ]);

        $body = $this->responseBody($response);

        $success = $response->successful()
            && Str::contains((string) data_get($body, 'status', ''), 'Success', ignoreCase: true);

        if (! $success) {
            return SmsResult::rejected(
                errorCode: $this->stringOrNull(data_get($body, 'statusCode')) ?? (string) $response->status(),
                errorMessage: $this->stringOrNull(data_get($body, 'responseResult')),
                response: $body,
            );
        }

        return SmsResult::accepted($this->stringOrNull(data_get($body, 'trxnId')), $body);
    }

    public function balance(): ?float
    {
        try {
            $response = Http::timeout($this->timeout)->asJson()->post(Str::finish($this->baseUrl, '/').'api/SmsSending/balanceCheck', [
                'UserName' => $this->username,
                'Apikey' => $this->apiKey,
            ]);
        } catch (Throwable) {
            return null;
        }

        $balance = data_get($response->json(), 'responseResult');

        return is_numeric($balance) ? (float) $balance : null;
    }
}
