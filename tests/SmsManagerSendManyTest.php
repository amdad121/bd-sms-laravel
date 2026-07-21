<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\SmsManager;

it('sends to every recipient and reports each result keyed by recipient', function (): void {
    $driver = new class implements SmsDriver
    {
        /** @var array<int, string> */
        public array $sentTo = [];

        public function send(string $to, string $message): bool
        {
            $this->sentTo[] = $to;

            return $to !== '018-fails';
        }

        public function balance(): ?float
        {
            return null;
        }
    };

    $manager = $this->app->make(SmsManager::class);
    $manager->extend('fake-driver', fn (): SmsDriver => $driver);

    config()->set('sms.default', 'fake-driver');

    $results = $manager->sendMany(['017-ok', '018-fails', '019-ok'], 'Hello there');

    expect($driver->sentTo)->toBe(['017-ok', '018-fails', '019-ok'])
        ->and($results)->toBe([
            '017-ok' => true,
            '018-fails' => false,
            '019-ok' => true,
        ]);
});

it('sends nothing and returns an empty array when given no recipients', function (): void {
    $results = $this->app->make(SmsManager::class)->sendMany([], 'Hello there');

    expect($results)->toBe([]);
});
