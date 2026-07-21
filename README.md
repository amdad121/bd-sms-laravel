# BD SMS for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/amdadulhaq/bd-sms-laravel.svg?style=flat-square)](https://packagist.org/packages/amdadulhaq/bd-sms-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/amdad121/bd-sms-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/amdad121/bd-sms-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/amdad121/bd-sms-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/amdad121/bd-sms-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/amdadulhaq/bd-sms-laravel.svg?style=flat-square)](https://packagist.org/packages/amdadulhaq/bd-sms-laravel)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-11%2F12%2F13-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Sponsor](https://img.shields.io/badge/Sponsor-%E2%9D%A4-pink?style=flat-square&logo=github)](https://github.com/sponsors/amdad121)

Send SMS from Laravel via Bangladeshi gateways (BulkSMSBD, MimSMS, SSL Wireless, Alpha SMS, GreenWeb BD) or any custom HTTP API.

## Requirements

- PHP 8.2, 8.3, 8.4, or 8.5
- Laravel 11, 12, or 13

## Installation

```bash
composer require amdadulhaq/bd-sms-laravel
```

The service provider and `Sms` facade are auto-discovered. Publish the config file:

```bash
php artisan vendor:publish --tag=sms-config
```

## Configuration

Set the default driver and credentials in your `.env`:

```env
SMS_DRIVER=bulksmsbd

BULKSMSBD_API_KEY=your-api-key
BULKSMSBD_SENDER_ID=your-sender-id
```

Or, for Alpha SMS:

```env
SMS_DRIVER=alphasms

ALPHASMS_API_KEY=your-api-key
ALPHASMS_SENDER_ID=your-sender-id
```

Or, for GreenWeb BD:

```env
SMS_DRIVER=greenweb

GREENWEB_TOKEN=your-token
```

See [`config/sms.php`](config/sms.php) for every driver's options. `SMS_DRIVER` defaults to `log`, so nothing breaks in local/testing environments without gateway credentials.

## Sending SMS

```php
use AmdadulHaq\BdSms\Facades\Sms;

Sms::send('01700000000', 'Your OTP is 4242');

// Send the same message to several recipients.
$results = Sms::sendMany(['01700000000', '01800000000'], 'Scheduled maintenance tonight at 11pm.');
// ['01700000000' => true, '01800000000' => true]

// Check remaining prepaid balance, if the driver supports it.
Sms::balance();

// Use a specific driver, or a driver other than the default, for one call.
Sms::driver('sslwireless')->send('01700000000', 'Hello via SSL Wireless');
```

`sendMany()` sends to each recipient individually and returns an array of results keyed by recipient number — it doesn't assume a gateway's batch API, so it works the same on every driver.

## Notifications

Implement `toSms()` on any notification and route it through the `sms` channel:

```php
use AmdadulHaq\BdSms\Notifications\SmsChannel;
use Illuminate\Notifications\Notification;

class OrderShipped extends Notification
{
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return "Your order #{$this->order->id} has shipped!";
    }
}
```

The channel resolves the recipient's number via `routeNotificationFor('sms', $notification)`, falling back to a `phone` property on the notifiable. Add a route method for full control:

```php
public function routeNotificationForSms(Notification $notification): string
{
    return $this->mobile_number;
}
```

## Adding your own gateway

Either configure the generic `http` driver (no code required — see the commented example in [`config/sms.php`](config/sms.php)), or register a custom one:

```php
use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\Contracts\SmsDriver;

Sms::extend('my-gateway', function ($app) {
    return new MyGatewayDriver(/* ... */);
});
```

Any driver just needs to implement `AmdadulHaq\BdSms\Contracts\SmsDriver`:

```php
interface SmsDriver
{
    public function send(string $to, string $message): bool;

    public function balance(): ?float;
}
```

## Testing

Use `Sms::fake()` to swap the real gateway with an in-memory fake and assert on what would have been sent, without dispatching anything or hitting the network:

```php
use AmdadulHaq\BdSms\Facades\Sms;

Sms::fake();

// ... code under test that calls Sms::send() or fires an "sms" notification ...

Sms::assertSent(fn (string $to, string $message) => $to === '01700000000' && str_contains($message, 'OTP'));
Sms::assertSentTo('01700000000');
Sms::assertSentCount(1);
Sms::assertNothingSent();
Sms::assertNotSent(fn (string $to) => $to === '01899999999');
```

## Running the package's own test suite

```bash
composer install
composer test          # Pest
composer analyse        # Larastan
composer lint:check    # Pint
```

## License

MIT. See [LICENSE.md](LICENSE.md).
