<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Notifications\SmsMessage;

it('creates a message with content and casts to string', function (): void {
    $message = SmsMessage::create('Your OTP is 4242');

    expect((string) $message)->toBe('Your OTP is 4242');
});

it('creates an empty message by default', function (): void {
    expect((string) SmsMessage::create())->toBe('');
});

it('allows setting content after creation', function (): void {
    $message = SmsMessage::create('first')->content('second');

    expect((string) $message)->toBe('second');
});

it('reports how many billable parts the content splits into', function (): void {
    expect(SmsMessage::create(str_repeat('a', 161))->segments())->toBe(2)
        ->and(SmsMessage::create('আপনার কোড ৪২৪২')->segments())->toBe(1);
});
