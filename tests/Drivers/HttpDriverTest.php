<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\HttpDriver;
use Illuminate\Support\Facades\Http;

it('substitutes {to} and {message} placeholders and posts as json by default', function (): void {
    Http::fake(['example.test/send' => Http::response(['ok' => true])]);

    $driver = new HttpDriver(
        url: 'https://example.test/send',
        method: 'post',
        payload: [
            'api_key' => 'secret',
            'to' => '{to}',
            'message' => '{message}',
        ],
    );

    $result = $driver->send('01700000000', 'Hello there');

    expect($result->accepted)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://example.test/send'
        && $request['api_key'] === 'secret'
        && $request['to'] === '8801700000000'
        && $request['message'] === 'Hello there'
        && $request->hasHeader('Content-Type', 'application/json'));
});

it('leaves non-string payload values untouched', function (): void {
    Http::fake(['example.test/send' => Http::response(['ok' => true])]);

    $driver = new HttpDriver(
        url: 'https://example.test/send',
        method: 'post',
        payload: [
            'to' => '{to}',
            'unicode' => true,
            'retries' => 3,
        ],
    );

    $driver->send('01700000000', 'Hello');

    Http::assertSent(fn ($request): bool => $request['unicode'] === true && $request['retries'] === 3);
});

it('appends the configured query string', function (): void {
    Http::fake(['example.test/send*' => Http::response(['ok' => true])]);

    $driver = new HttpDriver(
        url: 'https://example.test/send',
        method: 'post',
        payload: ['to' => '{to}'],
        query: ['token' => 'abc123'],
    );

    $driver->send('01700000000', 'Hello');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), 'token=abc123'));
});

it('supports other http methods', function (): void {
    Http::fake(['example.test/send*' => Http::response(['ok' => true])]);

    $driver = new HttpDriver(
        url: 'https://example.test/send',
        method: 'GET',
        payload: ['to' => '{to}'],
    );

    $driver->send('01700000000', 'Hello');

    Http::assertSent(fn ($request): bool => $request->method() === 'GET');
});

it('without a success path, treats any successful http response as sent', function (): void {
    Http::fake(['example.test/send' => Http::response(['status' => 'whatever'], 200)]);

    $driver = new HttpDriver(url: 'https://example.test/send', method: 'post', payload: []);

    expect($driver->send('01700000000', 'Hello')->accepted)->toBeTrue();
});

it('without a success path, treats a non-2xx http response as failure', function (): void {
    Http::fake(['example.test/send' => Http::response(['status' => 'whatever'], 500)]);

    $driver = new HttpDriver(url: 'https://example.test/send', method: 'post', payload: []);

    expect($driver->send('01700000000', 'Hello')->failed())->toBeTrue();
});

it('with a success path, only succeeds when the value matches', function (): void {
    Http::fake(['example.test/send' => Http::response(['status' => 'success'])]);

    $driver = new HttpDriver(
        url: 'https://example.test/send',
        method: 'post',
        payload: [],
        successPath: 'status',
        successValue: 'success',
    );

    expect($driver->send('01700000000', 'Hello')->accepted)->toBeTrue();
});

it('with a success path, fails when the value does not match', function (): void {
    Http::fake(['example.test/send' => Http::response(['status' => 'error'])]);

    $driver = new HttpDriver(
        url: 'https://example.test/send',
        method: 'post',
        payload: [],
        successPath: 'status',
        successValue: 'success',
    );

    expect($driver->send('01700000000', 'Hello')->failed())->toBeTrue();
});

it('has no balance support', function (): void {
    $driver = new HttpDriver(url: 'https://example.test/send', method: 'post', payload: []);

    expect($driver->balance())->toBeNull();
});

it('reads the message id from the configured path', function (): void {
    Http::fake(['example.test/send' => Http::response(['data' => ['id' => 'abc-1']])]);

    $driver = new HttpDriver(
        url: 'https://example.test/send',
        method: 'post',
        payload: ['to' => '{to}'],
        messageIdPath: 'data.id',
    );

    expect($driver->send('01700000000', 'Hello')->messageId)->toBe('abc-1');
});
