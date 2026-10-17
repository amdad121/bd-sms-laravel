<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Drivers\Concerns\NormalizesBangladeshiNumbers;
use AmdadulHaq\BdSms\Drivers\Concerns\ReadsGatewayResponses;
use AmdadulHaq\BdSms\SmsResult;
use Illuminate\Support\Facades\Http;

/**
 * Generic, config-only driver for any REST-based SMS gateway that isn't
 * shipped as a first-class driver (e.g. Elitbuzz, Alpha SMS, REVE Systems).
 *
 * Configure `url`, `method`, static `query`/`payload`, and placeholders
 * `{to}` / `{message}` inside the payload — no code required.
 */
class HttpDriver implements SmsDriver
{
    use NormalizesBangladeshiNumbers;
    use ReadsGatewayResponses;

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $query
     */
    public function __construct(
        private readonly string $url,
        private readonly string $method,
        private readonly array $payload,
        private readonly array $query = [],
        private readonly ?string $successPath = null,
        private readonly mixed $successValue = null,
        private readonly ?string $messageIdPath = null,
        private readonly int $timeout = 10,
    ) {}

    public function send(string $to, string $message): SmsResult
    {
        $payload = $this->resolvePlaceholders($this->payload, $to, $message);

        $response = Http::timeout($this->timeout)
            ->asJson()
            ->{strtolower($this->method)}($this->url.($this->query !== [] ? '?'.http_build_query($this->query) : ''), $payload);

        $body = $this->responseBody($response);

        $success = $response->successful()
            && ($this->successPath === null || data_get($body, $this->successPath) == $this->successValue);

        if (! $success) {
            return SmsResult::rejected(errorCode: (string) $response->status(), response: $body);
        }

        return SmsResult::accepted(
            $this->messageIdPath !== null ? $this->stringOrNull(data_get($body, $this->messageIdPath)) : null,
            $body,
        );
    }

    public function balance(): ?float
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolvePlaceholders(array $payload, string $to, string $message): array
    {
        return array_map(
            fn (mixed $value): mixed => is_string($value)
                ? str_replace(['{to}', '{message}'], [$this->normalizeNumber($to), $message], $value)
                : $value,
            $payload,
        );
    }
}
