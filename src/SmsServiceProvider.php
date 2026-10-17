<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Notifications\SmsChannel;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\ServiceProvider;
use Override;

class SmsServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/sms.php', 'sms');

        $this->app->singleton(SmsManager::class);

        $this->app->bind(SmsDriver::class, fn ($app): SmsDriver => $app->make(SmsManager::class));

        $this->app->alias(SmsManager::class, 'sms');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/sms.php' => config_path('sms.php'),
            ], 'sms-config');
        }

        $this->app->afterResolving(ChannelManager::class, function (ChannelManager $service): void {
            $service->extend('sms', fn ($app): SmsChannel => $app->make(SmsChannel::class));
        });
    }
}
