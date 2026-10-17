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

    expect($result->accepted)->toBeTrue();

    Http::assertSent(fn ($request): bool => $request->url() === 'https://api.mimsms.com/api/SmsSending/SMS'
        && $request['UserName'] === 'test-user'
        && $request['Apikey'] === 'test-api-key'
        && $request['MobileNumber'] === '8801700000000'
        && $request['SenderName'] === 'TestSender'
        && $request['Message'] === 'Hello there');
});

it('returns a failed result when the status does not contain success', function (): void {
    Http::fake([
        'api.mimsms.com/api/SmsSending/SMS' => Http::response(['statusCode' => '400', 'status' => 'Failed', 'responseResult' => 'Invalid sender']),
    ]);

    expect($this->driver->send('01700000000', 'Hello')->failed())->toBeTrue();
});

it('returns a failed result when the http response itself is unsuccessful', function (): void {
    Http::fake([
        'api.mimsms.com/api/SmsSending/SMS' => Http::response(['status' => 'Success'], 500),
    ]);

    expect($this->driver->send('01700000000', 'Hello')->failed())->toBeTrue();
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

it('returns the gateway message id when it accepts', function (): void {
    Http::fake(['api.mimsms.com/api/SmsSending/SMS' => Http::response(['statusCode' => '200', 'status' => 'Success', 'trxnId' => 'TRX-42', 'responseResult' => 'SMS submitted'])]);

    expect($this->driver->send('01700000000', 'Hello')->messageId)->toBe('TRX-42');
});

it('returns the gateway error code and message when it rejects', function (): void {
    Http::fake(['api.mimsms.com/api/SmsSending/SMS' => Http::response(['statusCode' => '208', 'status' => 'Failed', 'responseResult' => 'Invalid Sender Name'])]);

    $result = $this->driver->send('01700000000', 'Hello');

    expect($result->failed())->toBeTrue()
        ->and($result->errorCode)->toBe('208')
        ->and($result->errorMessage)->toBe('Invalid Sender Name');
});
