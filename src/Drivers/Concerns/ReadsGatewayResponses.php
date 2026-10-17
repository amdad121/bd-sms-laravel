<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers\Concerns;

use Illuminate\Http\Client\Response;

trait ReadsGatewayResponses
{
    /**
     * @return array<mixed>|string
     */
    private function responseBody(Response $response): array|string
    {
        $json = $response->json();

        return is_array($json) ? $json : $response->body();
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_scalar($value) && (string) $value !== '' ? (string) $value : null;
    }
}
