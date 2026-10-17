<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Events\SmsFailed;
use AmdadulHaq\BdSms\Events\SmsSent;
use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\SmsResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config()->set('sms.default', 'bulksmsbd');
    config()->set('sms.drivers.bulksmsbd', ['api_key' => 'key', 'sender_id' => 'sender']);
    Event::fake([SmsSent::class, SmsFailed::class]);
});

it('dispatches SmsSent with the driver and segment count when the gateway accepts', function (): void {
    Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202, 'message_id' => 9001])]);

    expect(Sms::send('01700000000', str_repeat('ক', 71))->accepted)->toBeTrue();

    Event::assertDispatched(SmsSent::class, fn (SmsSent $event): bool => $event->driver === 'bulksmsbd'
        && $event->to === '01700000000'
        && $event->segments() === 2
        && $event->result->messageId === '9001');
    Event::assertNotDispatched(SmsFailed::class);
});

it('dispatches SmsFailed with the gateway reason when it rejects', function (): void {
    Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 1007, 'error_message' => 'Balance Insufficient'])]);

    expect(Sms::send('01700000000', 'Hello')->failed())->toBeTrue();

    Event::assertDispatched(SmsFailed::class, fn (SmsFailed $event): bool => ! $event->exception instanceof Throwable
        && $event->result?->errorCode === '1007'
        && $event->result->errorMessage === 'Balance Insufficient');
    Event::assertNotDispatched(SmsSent::class);
});

it('dispatches SmsFailed with the exception and rethrows when the driver throws', function (): void {
    Http::fake(fn () => throw new ConnectionException('Timed out'));

    expect(fn (): SmsResult => Sms::send('01700000000', 'Hello'))->toThrow(ConnectionException::class);

    Event::assertDispatched(SmsFailed::class, fn (SmsFailed $event): bool => $event->exception instanceof ConnectionException
        && ! $event->result instanceof SmsResult);
});

it('dispatches an event per recipient for sendMany', function (): void {
    Http::fakeSequence('bulksmsbd.net/*')
        ->push(['response_code' => 202])
        ->push(['response_code' => 1007]);

    Sms::sendMany(['01700000000', '01800000000'], 'Hello');

    Event::assertDispatchedTimes(SmsSent::class, 1);
    Event::assertDispatchedTimes(SmsFailed::class, 1);
});

it('dispatches events when a driver is picked by name', function (): void {
    config()->set('sms.drivers.mimsms', ['username' => 'user', 'api_key' => 'key', 'sender_id' => 'sender']);
    Http::fake(['api.mimsms.com/*' => Http::response(['status' => 'Success'])]);

    Sms::driver('mimsms')->send('01700000000', 'Hello');

    Event::assertDispatched(SmsSent::class, fn (SmsSent $event): bool => $event->driver === 'mimsms');
});

it('dispatches events for custom drivers registered through extend', function (): void {
    Sms::extend('custom', fn (): SmsDriver => new class implements SmsDriver
    {
        public function send(string $to, string $message): SmsResult
        {
            return SmsResult::accepted();
        }

        public function balance(): ?float
        {
            return null;
        }
    });

    Sms::driver('custom')->send('01700000000', 'Hello');

    Event::assertDispatched(SmsSent::class, fn (SmsSent $event): bool => $event->driver === 'custom');
});
