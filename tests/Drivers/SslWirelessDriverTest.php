<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\SslWirelessDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->driver = new SslWirelessDriver(
        baseUrl: 'https://smsplus.sslwireless.com',
        apiToken: 'test-token',
        sid: 'TEST_SID',
    );
});

it('sends an sms and normalizes the recipient number', function (): void {
    Http::fake([
        'smsplus.sslwireless.com/api/v3/send-sms' => Http::response(['status' => 'SUCCESS']),
    ]);

    $result = $this->driver->send('01700000000', 'Hello there');

    expect($result)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://smsplus.sslwireless.com/api/v3/send-sms'
        && $request['api_token'] === 'test-token'
        && $request['sid'] === 'TEST_SID'
        && $request['msisdn'] === '8801700000000'
        && $request['sms'] === 'Hello there'
        && is_string($request['csms_id']));
});

it('returns false when the gateway status is not SUCCESS', function (): void {
    Http::fake([
        'smsplus.sslwireless.com/api/v3/send-sms' => Http::response(['status' => 'FAILED']),
    ]);

    expect($this->driver->send('01700000000', 'Hello'))->toBeFalse();
});

it('reads the balance from the gateway', function (): void {
    Http::fake([
        'smsplus.sslwireless.com/api/balance*' => Http::response(['balance' => 10.5]),
    ]);

    expect($this->driver->balance())->toBe(10.5);
});

it('returns null balance instead of throwing when the request fails', function (): void {
    Http::fake([
        'smsplus.sslwireless.com/api/balance*' => fn () => throw new ConnectionException('timed out'),
    ]);

    expect($this->driver->balance())->toBeNull();
});
