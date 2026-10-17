<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\GreenWebDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->driver = new GreenWebDriver(
        baseUrl: 'http://api.greenweb.com.bd',
        token: 'test-token',
    );
});

it('sends an sms and normalizes the recipient number', function (): void {
    Http::fake([
        'api.greenweb.com.bd/api.php' => Http::response([
            ['to' => '+8801700000000', 'message' => 'Hello there', 'status' => 'SENT', 'statusmsg' => 'SMS Sent Successfully'],
        ]),
    ]);

    $result = $this->driver->send('01700000000', 'Hello there');

    expect($result->accepted)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'http://api.greenweb.com.bd/api.php'
        && $request['token'] === 'test-token'
        && $request['to'] === '8801700000000'
        && $request['message'] === 'Hello there');
});

it('returns a failed result when the gateway reports a failed status', function (): void {
    Http::fake([
        'api.greenweb.com.bd/api.php' => Http::response([
            ['to' => '+8801700000000', 'message' => 'Hello', 'status' => 'FAILED', 'statusmsg' => 'Invalid Number'],
        ]),
    ]);

    expect($this->driver->send('01700000000', 'Hello')->failed())->toBeTrue();
});

it('reads the balance from the gateway', function (): void {
    Http::fake([
        'api.greenweb.com.bd/g_api.php*' => Http::response(['balance' => 123.45]),
    ]);

    expect($this->driver->balance())->toBe(123.45);
});

it('returns null balance instead of throwing when the request fails', function (): void {
    Http::fake([
        'api.greenweb.com.bd/g_api.php*' => fn () => throw new ConnectionException('timed out'),
    ]);

    expect($this->driver->balance())->toBeNull();
});

it('returns the gateway message id when it accepts', function (): void {
    Http::fake(['api.greenweb.com.bd/api.php' => Http::response([['to' => '8801700000000', 'status' => 'SENT', 'statusmsg' => 'SMS Sent Successfully']])]);

    expect($this->driver->send('01700000000', 'Hello')->messageId)->toBe(null);
});

it('returns the gateway error code and message when it rejects', function (): void {
    Http::fake(['api.greenweb.com.bd/api.php' => Http::response([['to' => '8801700000000', 'status' => 'FAILED', 'statusmsg' => 'Invalid Number']])]);

    $result = $this->driver->send('01700000000', 'Hello');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCode)->toBe('FAILED')
        ->and($result->errorMessage)->toBe('Invalid Number');
});
