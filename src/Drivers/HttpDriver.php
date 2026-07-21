<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Drivers\Concerns\NormalizesBangladeshiNumbers;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
    ) {}

    public function send(string $to, string $message): bool
    {
        $payload = $this->resolvePlaceholders($this->payload, $to, $message);

        $response = Http::asJson()
            ->{strtolower($this->method)}($this->url.($this->query !== [] ? '?'.http_build_query($this->query) : ''), $payload);

        if ($this->successPath === null) {
            return $response->successful();
        }

        $success = $response->successful() && data_get($response->json(), $this->successPath) == $this->successValue;

        if (! $success) {
            Log::warning('SMS gateway rejected an SMS.', ['url' => $this->url, 'body' => $response->body()]);
        }

        return $success;
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
