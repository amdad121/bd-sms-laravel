# BD SMS for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/amdadulhaq/bd-sms-laravel.svg?style=flat-square)](https://packagist.org/packages/amdadulhaq/bd-sms-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/amdad121/bd-sms-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/amdad121/bd-sms-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/amdad121/bd-sms-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/amdad121/bd-sms-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/amdadulhaq/bd-sms-laravel.svg?style=flat-square)](https://packagist.org/packages/amdadulhaq/bd-sms-laravel)
[![PHP Version](https://img.shields.io/badge/PHP-8.3%2B-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel Version](https://img.shields.io/badge/Laravel-12%2F13-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Sponsor](https://img.shields.io/badge/Sponsor-%E2%9D%A4-pink?style=flat-square&logo=github)](https://github.com/sponsors/amdad121)

Send SMS from Laravel via Bangladeshi gateways (BulkSMSBD, MimSMS, SSL Wireless, Alpha SMS, GreenWeb BD) or any custom HTTP API.

## Requirements

- PHP 8.3, 8.4, or 8.5
- Laravel 12 or 13

## Installation

```bash
composer require amdadulhaq/bd-sms-laravel
```

The service provider and `Sms` facade are auto-discovered. Publish the config file:

```bash
php artisan vendor:publish --tag=sms-config
```

## Configuration

Set `SMS_DRIVER` to the gateway you want, then fill in that driver's credentials. `SMS_DRIVER` defaults to `log`, so nothing breaks in local/testing environments without gateway credentials. See [`config/sms.php`](config/sms.php) for every option, including per-driver `base_url` overrides.

Every HTTP driver gives up on a gateway after `SMS_TIMEOUT` seconds (default `10`), so a hanging gateway can't block a request or queue worker. A timeout throws `Illuminate\Http\Client\ConnectionException`, which also dispatches `SmsFailed`.

### Log (default)

Writes each SMS to the application log instead of sending it — the default when `SMS_DRIVER` is unset, no configuration needed.

```env
SMS_DRIVER=log
```

### BulkSMSBD

```env
SMS_DRIVER=bulksmsbd

BULKSMSBD_API_KEY=your-api-key
BULKSMSBD_SENDER_ID=your-sender-id
```

### MimSMS

```env
SMS_DRIVER=mimsms

MIMSMS_USERNAME=your-account-email
MIMSMS_API_KEY=your-api-key
MIMSMS_SENDER_ID=your-sender-id
```

### SSL Wireless

```env
SMS_DRIVER=sslwireless

SSLWIRELESS_API_TOKEN=your-api-token
SSLWIRELESS_SID=your-sid
```

### Alpha SMS

```env
SMS_DRIVER=alphasms

ALPHASMS_API_KEY=your-api-key
ALPHASMS_SENDER_ID=your-sender-id
```

### GreenWeb BD

```env
SMS_DRIVER=greenweb

GREENWEB_TOKEN=your-token
```

### Any other gateway (generic HTTP driver)

For any REST gateway without a first-class driver (Elitbuzz, REVE Systems, etc.), configure the `http` driver directly in [`config/sms.php`](config/sms.php) — no code required. Use `{to}` and `{message}` as placeholders anywhere in the payload:

```php
'http' => [
    'url' => env('SMS_HTTP_URL'),
    'method' => 'post',
    'query' => [],
    'payload' => [
        'api_key' => env('SMS_HTTP_API_KEY'),
        'to' => '{to}',
        'message' => '{message}',
    ],
    'success_path' => 'status',
    'success_value' => 'success',
    'message_id_path' => 'data.id', // optional
],
```

```env
SMS_DRIVER=http

SMS_HTTP_URL=https://gateway.example.com/send
SMS_HTTP_API_KEY=your-api-key
```

`success_path` is a dot-notation path into the JSON response (via `data_get()`); the send is considered successful when the value there loosely equals `success_value`. Omit both to just check the HTTP response status. `message_id_path` is where the gateway's message ID sits in the response, if it returns one. This driver's `balance()` always returns `null` since there's no generic way to know a gateway's balance endpoint.

## Sending SMS

```php
use AmdadulHaq\BdSms\Facades\Sms;

$result = Sms::send('01700000000', 'Your OTP is 4242');

if ($result->failed()) {
    report("SMS rejected: {$result->errorCode} {$result->errorMessage}");
}

$result->messageId; // the gateway's ID for this message, when it returns one

// Send the same message to several recipients.
$results = Sms::sendMany(['01700000000', '01800000000'], 'Scheduled maintenance tonight at 11pm.');
// ['01700000000' => SmsResult, '01800000000' => SmsResult]

// Check remaining prepaid balance, if the driver supports it.
Sms::balance();

// Use a specific driver, or a driver other than the default, for one call.
Sms::driver('sslwireless')->send('01700000000', 'Hello via SSL Wireless');
```

`sendMany()` sends to each recipient individually and returns an array of results keyed by recipient number — it doesn't assume a gateway's batch API, so it works the same on every driver.

### The result

Every send returns an `AmdadulHaq\BdSms\SmsResult`. A rejection is a normal result, not an exception; only transport errors such as a timeout are thrown.

| Property | Meaning |
| --- | --- |
| `accepted` / `failed()` | Whether the gateway took the message |
| `messageId` | The gateway's ID for the message (BulkSMSBD `message_id`, MimSMS `trxnId`, SSL Wireless `reference_id`, Alpha SMS `request_id`); `null` when the gateway doesn't return one, e.g. GreenWeb BD |
| `errorCode`, `errorMessage` | The gateway's own code and text for a rejection, falling back to the HTTP status code |
| `response` | The decoded JSON response, or the raw body when it isn't JSON |

The drivers don't write to your log. Listen for `SmsFailed` to log or alert on rejections the way you want.

## Events

Every send dispatches one event per recipient — `Sms::send()`, `Sms::sendMany()`, `Sms::driver('name')->send()`, custom drivers registered with `extend()`, and the `sms` notification channel — so you can log, meter or alert on SMS without wrapping the driver:

| Event | When | Properties |
| --- | --- | --- |
| `AmdadulHaq\BdSms\Events\SmsSent` | The gateway accepted the message | `driver`, `to`, `message`, `result`, `segments()` |
| `AmdadulHaq\BdSms\Events\SmsFailed` | The gateway rejected it, or the driver threw | `driver`, `to`, `message`, and either `result` (a rejection) or `exception` (the driver threw) |

```php
use AmdadulHaq\BdSms\Events\SmsSent;
use Illuminate\Support\Facades\Event;

Event::listen(function (SmsSent $event): void {
    // e.g. count $event->segments() towards today's usage
});
```

`$event->message` is the full text, which for an OTP includes the code. Avoid logging it as-is from a listener.

`Sms::driver()` returns an `AmdadulHaq\BdSms\SmsSender`, the wrapper that dispatches these events. Use `getName()` for the driver name and `getDriver()` for the underlying `SmsDriver`. Only a driver instantiated by hand (`new BulkSmsBdDriver(...)`) skips the events.

## Counting SMS parts

Gateways bill per part. A part holds 160 characters of plain GSM-7 text (153 each once a message is split), but only 70 (67 each once split) as soon as the message contains any character outside GSM-7, such as Bangla or an emoji. GSM-7 extension characters (`^ { } \ [ ] ~ | €`) take two slots each, and an emoji takes two Unicode units.

```php
use AmdadulHaq\BdSms\Notifications\SmsMessage;
use AmdadulHaq\BdSms\Support\SmsSegments;

SmsSegments::count(str_repeat('a', 161));   // 2
SmsSegments::count('আপনার কোড ৪২৪২');        // 1
SmsSegments::isUnicode('আপনার কোড ৪২৪২');    // true
SmsSegments::length('Price: €5');           // 10

SmsMessage::create($text)->segments();
```

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

When the gateway rejects the message, the channel throws `AmdadulHaq\BdSms\Exceptions\CouldNotSendSms`, whose message includes the gateway's error code and text and whose `result` property holds the full `SmsResult`. Laravel then dispatches `NotificationFailed` instead of `NotificationSent`, and a queued notification fails and is retried like any other failed job.

## Adding your own gateway

For anything the generic `http` driver (see [Configuration](#configuration)) can't express, register a custom driver:

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
    public function send(string $to, string $message): SmsResult;

    public function balance(): ?float;
}
```

Return `SmsResult::accepted($messageId)` or `SmsResult::rejected($errorCode, $errorMessage)`, and let transport errors throw.

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

To test how your code handles a rejection, tell the fake to fail every send, or only the ones matching a truth test. Failed sends return a rejected `SmsResult` and are not recorded as sent:

```php
Sms::fake()->fail();

Sms::fake()->fail(fn (string $to, string $message) => $to === '01899999999');
```

`Sms::driver('name')` returns the fake itself, so code that picks a gateway is recorded too. The fake does not dispatch `SmsSent` or `SmsFailed`, the same way Laravel's mail fake skips mail events.

## Running the package's own test suite

```bash
composer install
composer test          # Pest
composer analyse        # Larastan
composer lint:check    # Pint
```

## License

MIT. See [LICENSE.md](LICENSE.md).
