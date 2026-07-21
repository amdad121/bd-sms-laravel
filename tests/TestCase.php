<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Tests;

use AmdadulHaq\BdSms\SmsServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            SmsServiceProvider::class,
        ];
    }
}
