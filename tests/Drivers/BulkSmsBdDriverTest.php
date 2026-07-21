<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\BulkSmsBdDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->driver = new BulkSmsBdDriver(
        baseUrl: 'https://bulksmsbd.net',
        apiKey: 'test-api-key',
        senderId: 'TestSender',
    );
});

it('sends an sms and normalizes the recipient number', function (): void {
    Http::fake([
        'bulksmsbd.net/api/smsapi' => Http::response(['response_code' => 202]),
    ]);

    $result = $this->driver->send('01700000000', 'Hello there');

    expect($result)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://bulksmsbd.net/api/smsapi'
        && $request['api_key'] === 'test-api-key'
        && $request['senderid'] === 'TestSender'
        && $request['number'] === '8801700000000'
        && $request['message'] === 'Hello there'
        && $request['type'] === 'text');
});

it('does not re-prefix a number that already has the country code', function (): void {
    Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202])]);

    $this->driver->send('8801700000000', 'Hello');

    Http::assertSent(fn ($request): bool => $request['number'] === '8801700000000');
});

it('returns false when the gateway rejects the sms', function (): void {
    Http::fake([
        'bulksmsbd.net/api/smsapi' => Http::response(['response_code' => 1002]),
    ]);

    expect($this->driver->send('01700000000', 'Hello'))->toBeFalse();
});

it('reads the balance from the gateway', function (): void {
    Http::fake([
        'bulksmsbd.net/api/getBalanceApi*' => Http::response(['balance' => 123.45]),
    ]);

    expect($this->driver->balance())->toBe(123.45);
});

it('returns null balance when the gateway response has no numeric balance', function (): void {
    Http::fake([
        'bulksmsbd.net/api/getBalanceApi*' => Http::response(['balance' => 'not-a-number']),
    ]);

    expect($this->driver->balance())->toBeNull();
});

it('returns null balance instead of throwing when the request fails', function (): void {
    Http::fake([
        'bulksmsbd.net/api/getBalanceApi*' => fn () => throw new ConnectionException('timed out'),
    ]);

    expect($this->driver->balance())->toBeNull();
});
