<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Drivers\AlphaSmsDriver;
use AmdadulHaq\BdSms\Drivers\BulkSmsBdDriver;
use AmdadulHaq\BdSms\Drivers\GreenWebDriver;
use AmdadulHaq\BdSms\Drivers\HttpDriver;
use AmdadulHaq\BdSms\Drivers\LogDriver;
use AmdadulHaq\BdSms\Drivers\MimSmsDriver;
use AmdadulHaq\BdSms\Drivers\SslWirelessDriver;
use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\Testing\SmsFake;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Manager;
use InvalidArgumentException;

/**
 * @mixin SmsSender
 *
 * @method SmsSender driver(string|null $driver = null)
 */
class SmsManager extends Manager implements SmsDriver
{
    public function getDefaultDriver(): string
    {
        return $this->config->get('sms.default', 'log');
    }

    protected function createLogDriver(): LogDriver
    {
        return new LogDriver;
    }

    protected function createBulksmsbdDriver(): BulkSmsBdDriver
    {
        $config = $this->config->get('sms.drivers.bulksmsbd', []);

        return new BulkSmsBdDriver(
            baseUrl: $config['base_url'] ?? 'https://bulksmsbd.net',
            apiKey: $config['api_key'] ?? '',
            senderId: $config['sender_id'] ?? '',
            timeout: $this->timeout(),
        );
    }

    protected function createMimsmsDriver(): MimSmsDriver
    {
        $config = $this->config->get('sms.drivers.mimsms', []);

        return new MimSmsDriver(
            baseUrl: $config['base_url'] ?? 'https://api.mimsms.com',
            username: $config['username'] ?? '',
            apiKey: $config['api_key'] ?? '',
            senderId: $config['sender_id'] ?? '',
            timeout: $this->timeout(),
        );
    }

    protected function createSslwirelessDriver(): SslWirelessDriver
    {
        $config = $this->config->get('sms.drivers.sslwireless', []);

        return new SslWirelessDriver(
            baseUrl: $config['base_url'] ?? 'https://smsplus.sslwireless.com',
            apiToken: $config['api_token'] ?? '',
            sid: $config['sid'] ?? '',
            timeout: $this->timeout(),
        );
    }

    protected function createAlphasmsDriver(): AlphaSmsDriver
    {
        $config = $this->config->get('sms.drivers.alphasms', []);

        return new AlphaSmsDriver(
            baseUrl: $config['base_url'] ?? 'https://api.sms.net.bd',
            apiKey: $config['api_key'] ?? '',
            senderId: $config['sender_id'] ?? null,
            timeout: $this->timeout(),
        );
    }

    protected function createGreenwebDriver(): GreenWebDriver
    {
        $config = $this->config->get('sms.drivers.greenweb', []);

        return new GreenWebDriver(
            baseUrl: $config['base_url'] ?? 'http://api.greenweb.com.bd',
            token: $config['token'] ?? '',
            timeout: $this->timeout(),
        );
    }

    protected function createHttpDriver(): HttpDriver
    {
        $config = $this->config->get('sms.drivers.http', []);

        return new HttpDriver(
            url: $config['url'] ?? '',
            method: $config['method'] ?? 'post',
            payload: $config['payload'] ?? [],
            query: $config['query'] ?? [],
            successPath: $config['success_path'] ?? null,
            successValue: $config['success_value'] ?? null,
            messageIdPath: $config['message_id_path'] ?? null,
            timeout: $this->timeout(),
        );
    }

    private function timeout(): int
    {
        return (int) $this->config->get('sms.timeout', 10);
    }

    /**
     * Wrap every resolved driver, including custom ones from extend(), in an
     * SmsSender so events are dispatched however the driver is reached.
     *
     * @param  string  $driver
     */
    protected function createDriver($driver): SmsSender
    {
        $resolved = parent::createDriver($driver);

        if (! $resolved instanceof SmsDriver) {
            throw new InvalidArgumentException(sprintf('SMS driver [%s] must implement %s.', $driver, SmsDriver::class));
        }

        return new SmsSender(
            $driver,
            $resolved,
            $this->container->bound(Dispatcher::class) ? $this->container->make(Dispatcher::class) : null,
        );
    }

    /**
     * Send an SMS using the default driver.
     */
    public function send(string $to, string $message): SmsResult
    {
        return $this->driver()->send($to, $message);
    }

    /**
     * Send the same message to multiple recipients using the default driver.
     *
     * @param  array<int, string>  $to
     * @return array<string, SmsResult>
     */
    public function sendMany(array $to, string $message): array
    {
        return $this->driver()->sendMany($to, $message);
    }

    /**
     * Get the remaining prepaid balance on the default gateway account, or null if unavailable.
     */
    public function balance(): ?float
    {
        return $this->driver()->balance();
    }

    /**
     * Swap the underlying facade instance with a fake for asserting on sent SMS in tests.
     */
    public static function fake(): SmsFake
    {
        $fake = new SmsFake;

        Sms::swap($fake);

        return $fake;
    }
}
