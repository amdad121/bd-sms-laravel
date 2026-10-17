<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\SmsResult;
use AmdadulHaq\BdSms\Testing\SmsFake;
use PHPUnit\Framework\ExpectationFailedException;

it('returns a fake and swaps the facade root', function (): void {
    $fake = Sms::fake();

    expect($fake)->toBeInstanceOf(SmsFake::class)
        ->and(Sms::getFacadeRoot())->toBe($fake);
});

it('records sent messages instead of dispatching them', function (): void {
    Sms::fake();

    Sms::send('017-111-1111', 'Your code is 1234');

    Sms::assertSent(fn (string $to, string $message): bool => $to === '017-111-1111' && $message === 'Your code is 1234');
});

it('fails the assertion when no matching sms was sent', function (): void {
    Sms::fake();

    Sms::send('017-111-1111', 'Your code is 1234');

    expect(fn () => Sms::assertSent(fn (string $to): bool => $to === '018-000-0000'))
        ->toThrow(ExpectationFailedException::class);
});

it('asserts nothing was sent', function (): void {
    Sms::fake();

    Sms::assertNothingSent();
});

it('fails assertNothingSent once an sms goes out', function (): void {
    Sms::fake();

    Sms::send('017-111-1111', 'Hi');

    expect(fn () => Sms::assertNothingSent())
        ->toThrow(ExpectationFailedException::class);
});

it('asserts an sms was sent to a specific recipient', function (): void {
    Sms::fake();

    Sms::send('017-111-1111', 'Hi Alice');
    Sms::send('018-222-2222', 'Hi Bob');

    Sms::assertSentTo('018-222-2222', fn (string $to, string $message): bool => $message === 'Hi Bob');
});

it('asserts the total number of sms sent, including via sendMany', function (): void {
    $fake = Sms::fake();

    Sms::sendMany(['017-111-1111', '018-222-2222'], 'Broadcast');

    Sms::assertSentCount(2);
    expect($fake->count())->toBe(2);
});

it('does not fake other manager methods like balance', function (): void {
    Sms::fake();

    expect(Sms::balance())->toBeNull();
});

it('rejects every send once told to fail, without recording them as sent', function (): void {
    Sms::fake()->fail();

    expect(Sms::send('017-111-1111', 'Hi')->failed())->toBeTrue();

    Sms::assertNothingSent();
});

it('rejects only the sends matching the failure truth test', function (): void {
    Sms::fake()->fail(fn (string $to): bool => $to === '018-222-2222');

    expect(array_map(fn (SmsResult $result): bool => $result->accepted, Sms::sendMany(['017-111-1111', '018-222-2222'], 'Hi')))
        ->toBe(['017-111-1111' => true, '018-222-2222' => false]);

    Sms::assertSentCount(1);
    Sms::assertSentTo('017-111-1111');
});

it('records sends made through a named driver', function (): void {
    Sms::fake();

    Sms::driver('mimsms')->send('017-111-1111', 'Hi');

    Sms::assertSentTo('017-111-1111');
});
