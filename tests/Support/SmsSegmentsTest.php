<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Support\SmsSegments;

it('counts plain english messages in 160 character parts, 153 once split', function (string $message, int $parts): void {
    expect(SmsSegments::count($message))->toBe($parts);
})->with([
    'empty' => ['', 0],
    'exactly one part' => [str_repeat('a', 160), 1],
    'just over one part' => [str_repeat('a', 161), 2],
    'exactly two parts' => [str_repeat('a', 306), 2],
    'just over two parts' => [str_repeat('a', 307), 3],
]);

it('counts gsm extension characters as two', function (): void {
    expect(SmsSegments::length('[€]'))->toBe(6)
        ->and(SmsSegments::count(str_repeat('a', 159).'€'))->toBe(2)
        ->and(SmsSegments::isUnicode('Price: €5 {50%}'))->toBeFalse();
});

it('counts bangla and other unicode messages in 70 unit parts, 67 once split', function (string $message, int $parts): void {
    expect(SmsSegments::isUnicode($message))->toBeTrue()
        ->and(SmsSegments::count($message))->toBe($parts);
})->with([
    'exactly one part' => [str_repeat('ক', 70), 1],
    'just over one part' => [str_repeat('ক', 71), 2],
    'exactly two parts' => [str_repeat('ক', 134), 2],
    'just over two parts' => [str_repeat('ক', 135), 3],
    'one non gsm character switches the whole message' => [str_repeat('a', 70).'ক', 2],
]);

it('counts an emoji as two unicode units', function (): void {
    expect(SmsSegments::length('😀'))->toBe(2)
        ->and(SmsSegments::count(str_repeat('😀', 35)))->toBe(1)
        ->and(SmsSegments::count(str_repeat('😀', 36)))->toBe(2);
});
