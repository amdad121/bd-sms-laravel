<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Drivers\LogDriver;
use Illuminate\Support\Facades\Log;

it('logs the message and reports success without dispatching anything', function (): void {
    Log::shouldReceive('info')
        ->once()
        ->with('SMS (not sent — log driver).', [
            'to' => '01700000000',
            'message' => 'Hello',
        ]);

    $driver = new LogDriver;

    expect($driver->send('01700000000', 'Hello'))->toBeTrue();
});

it('has no balance', function (): void {
    expect((new LogDriver)->balance())->toBeNull();
});
