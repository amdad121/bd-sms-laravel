<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Drivers\AlphaSmsDriver;
use AmdadulHaq\BdSms\Drivers\BulkSmsBdDriver;
use AmdadulHaq\BdSms\Drivers\GreenWebDriver;
use AmdadulHaq\BdSms\Drivers\HttpDriver;
use AmdadulHaq\BdSms\Drivers\LogDriver;
use AmdadulHaq\BdSms\Drivers\MimSmsDriver;
use AmdadulHaq\BdSms\Drivers\SslWirelessDriver;
use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\SmsManager;
use Illuminate\Support\Facades\Http;

it('defaults to the log driver', function (): void {
    expect($this->app->make(SmsManager::class)->driver())->toBeInstanceOf(LogDriver::class);
});

it('resolves the bulksmsbd driver from config', function (): void {
    config()->set('sms.drivers.bulksmsbd', [
        'base_url' => 'https://custom.bulksmsbd.net',
        'api_key' => 'key',
        'sender_id' => 'sender',
    ]);

    $driver = $this->app->make(SmsManager::class)->driver('bulksmsbd');

    expect($driver)->toBeInstanceOf(BulkSmsBdDriver::class);

    Http::fake(['custom.bulksmsbd.net/*' => Http::response(['response_code' => 202])]);
    $driver->send('01700000000', 'Hello');
    Http::assertSent(fn ($request): bool => $request['api_key'] === 'key' && $request['senderid'] === 'sender');
});

it('resolves the mimsms driver from config', function (): void {
    config()->set('sms.drivers.mimsms', [
        'base_url' => 'https://custom.mimsms.com',
        'username' => 'user',
        'api_key' => 'key',
        'sender_id' => 'sender',
    ]);

    $driver = $this->app->make(SmsManager::class)->driver('mimsms');

    expect($driver)->toBeInstanceOf(MimSmsDriver::class);

    Http::fake(['custom.mimsms.com/*' => Http::response(['status' => 'Success'])]);
    $driver->send('01700000000', 'Hello');
    Http::assertSent(fn ($request): bool => $request['UserName'] === 'user' && $request['SenderName'] === 'sender');
});

it('resolves the sslwireless driver from config', function (): void {
    config()->set('sms.drivers.sslwireless', [
        'base_url' => 'https://custom.sslwireless.com',
        'api_token' => 'token',
        'sid' => 'sid',
    ]);

    $driver = $this->app->make(SmsManager::class)->driver('sslwireless');

    expect($driver)->toBeInstanceOf(SslWirelessDriver::class);

    Http::fake(['custom.sslwireless.com/*' => Http::response(['status' => 'SUCCESS'])]);
    $driver->send('01700000000', 'Hello');
    Http::assertSent(fn ($request): bool => $request['api_token'] === 'token' && $request['sid'] === 'sid');
});

it('resolves the alphasms driver from config', function (): void {
    config()->set('sms.drivers.alphasms', [
        'base_url' => 'https://custom.sms.net.bd',
        'api_key' => 'key',
        'sender_id' => 'sender',
    ]);

    $driver = $this->app->make(SmsManager::class)->driver('alphasms');

    expect($driver)->toBeInstanceOf(AlphaSmsDriver::class);

    Http::fake(['custom.sms.net.bd/*' => Http::response(['error' => 0])]);
    $driver->send('01700000000', 'Hello');
    Http::assertSent(fn ($request): bool => $request['api_key'] === 'key' && $request['sender_id'] === 'sender');
});

it('resolves the greenweb driver from config', function (): void {
    config()->set('sms.drivers.greenweb', [
        'base_url' => 'http://custom.greenweb.com.bd',
        'token' => 'token',
    ]);

    $driver = $this->app->make(SmsManager::class)->driver('greenweb');

    expect($driver)->toBeInstanceOf(GreenWebDriver::class);

    Http::fake(['custom.greenweb.com.bd/*' => Http::response([['status' => 'SENT']])]);
    $driver->send('01700000000', 'Hello');
    Http::assertSent(fn ($request): bool => $request['token'] === 'token');
});

it('resolves the http driver from config', function (): void {
    config()->set('sms.drivers.http', [
        'url' => 'https://custom.example/send',
        'method' => 'post',
        'payload' => ['to' => '{to}'],
        'success_path' => 'status',
        'success_value' => 'ok',
    ]);

    $driver = $this->app->make(SmsManager::class)->driver('http');

    expect($driver)->toBeInstanceOf(HttpDriver::class);

    Http::fake(['custom.example/send' => Http::response(['status' => 'ok'])]);
    expect($driver->send('01700000000', 'Hello'))->toBeTrue();
});

it('uses the configured default driver', function (): void {
    config()->set('sms.default', 'log');

    expect($this->app->make(SmsManager::class)->driver())->toBeInstanceOf(LogDriver::class);
});

it('allows registering a custom driver via extend()', function (): void {
    $custom = new class implements SmsDriver
    {
        public bool $wasSent = false;

        public function send(string $to, string $message): bool
        {
            $this->wasSent = true;

            return true;
        }

        public function balance(): float
        {
            return 99.0;
        }
    };

    $manager = $this->app->make(SmsManager::class);
    $manager->extend('custom', fn (): SmsDriver => $custom);

    config()->set('sms.default', 'custom');

    expect($manager->send('01700000000', 'Hello'))->toBeTrue()
        ->and($custom->wasSent)->toBeTrue()
        ->and($manager->balance())->toBe(99.0);
});

it('proxies send() and balance() through the facade to the default driver', function (): void {
    config()->set('sms.default', 'log');

    expect(Sms::send('01700000000', 'Hello'))->toBeTrue()
        ->and(Sms::balance())->toBeNull();
});
