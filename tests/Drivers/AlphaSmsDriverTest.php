<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\AlphaSmsDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->driver = new AlphaSmsDriver(
        baseUrl: 'https://api.sms.net.bd',
        apiKey: 'test-api-key',
        senderId: 'TestSender',
    );
});

it('sends an sms and normalizes the recipient number', function (): void {
    Http::fake([
        'api.sms.net.bd/sendsms' => Http::response(['error' => 0, 'msg' => 'Request successfully submitted', 'data' => ['request_id' => 123]]),
    ]);

    $result = $this->driver->send('01700000000', 'Hello there');

    expect($result->accepted)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.sms.net.bd/sendsms'
        && $request['api_key'] === 'test-api-key'
        && $request['sender_id'] === 'TestSender'
        && $request['to'] === '8801700000000'
        && $request['msg'] === 'Hello there');
});

it('omits sender_id when none is configured', function (): void {
    $driver = new AlphaSmsDriver(baseUrl: 'https://api.sms.net.bd', apiKey: 'test-api-key');

    Http::fake(['api.sms.net.bd/sendsms' => Http::response(['error' => 0])]);

    $driver->send('01700000000', 'Hello');

    Http::assertSent(fn ($request): bool => ! $request->hasHeader('sender_id') && ! array_key_exists('sender_id', $request->data()));
});

it('returns a failed result when the gateway rejects the sms', function (): void {
    Http::fake([
        'api.sms.net.bd/sendsms' => Http::response(['error' => 417]),
    ]);

    expect($this->driver->send('01700000000', 'Hello')->failed())->toBeTrue();
});

it('reads the balance from the gateway', function (): void {
    Http::fake([
        'api.sms.net.bd/user/balance/*' => Http::response(['error' => 0, 'msg' => 'Success', 'data' => ['balance' => '123.45']]),
    ]);

    expect($this->driver->balance())->toBe(123.45);
});

it('returns null balance instead of throwing when the request fails', function (): void {
    Http::fake([
        'api.sms.net.bd/user/balance/*' => fn () => throw new ConnectionException('timed out'),
    ]);

    expect($this->driver->balance())->toBeNull();
});

it('returns the gateway message id when it accepts', function (): void {
    Http::fake(['api.sms.net.bd/sendsms' => Http::response(['error' => 0, 'msg' => 'Request successfully submitted', 'data' => ['request_id' => 556]])]);

    expect($this->driver->send('01700000000', 'Hello')->messageId)->toBe('556');
});

it('returns the gateway error code and message when it rejects', function (): void {
    Http::fake(['api.sms.net.bd/sendsms' => Http::response(['error' => 405, 'msg' => 'Authorization required'])]);

    $result = $this->driver->send('01700000000', 'Hello');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCode)->toBe('405')
        ->and($result->errorMessage)->toBe('Authorization required');
});

it('treats a response without an error code as a rejection', function (): void {
    Http::fake(['api.sms.net.bd/sendsms' => Http::response('<html>Bad Gateway</html>', 502)]);

    $result = $this->driver->send('01700000000', 'Hello');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCode)->toBe('502')
        ->and($result->response)->toBe('<html>Bad Gateway</html>');
});
