<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\MimSmsDriver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    $this->driver = new MimSmsDriver(
        baseUrl: 'https://api.mimsms.com',
        username: 'test-user',
        apiKey: 'test-api-key',
        senderId: 'TestSender',
    );
});

it('sends an sms and normalizes the recipient number', function (): void {
    Http::fake([
        'api.mimsms.com/api/SmsSending/SMS' => Http::response(['statusCode' => '200', 'status' => 'Success', 'trxnId' => '123', 'responseResult' => 'SMS Send Successfuly']),
    ]);

    $result = $this->driver->send('01700000000', 'Hello there');

    expect($result)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.mimsms.com/api/SmsSending/SMS'
        && $request['UserName'] === 'test-user'
        && $request['Apikey'] === 'test-api-key'
        && $request['MobileNumber'] === '8801700000000'
        && $request['SenderName'] === 'TestSender'
        && $request['Message'] === 'Hello there');
});

it('returns false when the status does not contain success', function (): void {
    Http::fake([
        'api.mimsms.com/api/SmsSending/SMS' => Http::response(['statusCode' => '400', 'status' => 'Failed', 'responseResult' => 'Invalid sender']),
    ]);

    expect($this->driver->send('01700000000', 'Hello'))->toBeFalse();
});

it('returns false when the http response itself is unsuccessful', function (): void {
    Http::fake([
        'api.mimsms.com/api/SmsSending/SMS' => Http::response(['status' => 'Success'], 500),
    ]);

    expect($this->driver->send('01700000000', 'Hello'))->toBeFalse();
});

it('reads the balance from the gateway', function (): void {
    Http::fake([
        'api.mimsms.com/api/SmsSending/balanceCheck' => Http::response(['statusCode' => '200', 'status' => 'Ok', 'responseResult' => '999.54']),
    ]);

    expect($this->driver->balance())->toBe(999.54);
});

it('returns null balance instead of throwing when the request fails', function (): void {
    Http::fake([
        'api.mimsms.com/api/SmsSending/balanceCheck' => fn () => throw new ConnectionException('timed out'),
    ]);

    expect($this->driver->balance())->toBeNull();
});
