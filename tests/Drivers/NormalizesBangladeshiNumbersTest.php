<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\Concerns\NormalizesBangladeshiNumbers;

beforeEach(function (): void {
    $this->normalizer = new class
    {
        use NormalizesBangladeshiNumbers;

        public function normalize(string $number): string
        {
            return $this->normalizeNumber($number);
        }
    };
});

it('prefixes a local number with the country code', function (): void {
    expect($this->normalizer->normalize('01700000000'))->toBe('8801700000000');
});

it('leaves a number that already has the country code untouched', function (): void {
    expect($this->normalizer->normalize('8801700000000'))->toBe('8801700000000');
});

it('strips spaces, dashes, and a leading plus sign', function (): void {
    expect($this->normalizer->normalize('+880 170-000-0000'))->toBe('8801700000000');
});
