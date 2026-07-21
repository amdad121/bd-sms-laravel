<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Drivers\Concerns;

use Illuminate\Support\Str;

trait NormalizesBangladeshiNumbers
{
    /**
     * Normalize a Bangladeshi number to the 88XXXXXXXXXXX format most gateways expect.
     */
    protected function normalizeNumber(string $number): string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';

        return Str::startsWith($digits, '88') ? $digits : '88'.$digits;
    }
}
