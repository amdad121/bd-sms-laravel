<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Facades;

use AmdadulHaq\BdSms\SmsManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static bool send(string $to, string $message)
 * @method static array<string, bool> sendMany(array<int, string> $to, string $message)
 * @method static float|null balance()
 * @method static \AmdadulHaq\BdSms\Contracts\SmsDriver driver(string|null $driver = null)
 * @method static \AmdadulHaq\BdSms\SmsManager extend(string $driver, \Closure $callback)
 * @method static \AmdadulHaq\BdSms\Testing\SmsFake fake()
 *
 * @see SmsManager
 */
class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsManager::class;
    }
}
