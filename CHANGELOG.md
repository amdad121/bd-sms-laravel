# Changelog

All notable changes to `bd-sms-laravel` will be documented in this file.

## v2.0.0 - 2026-10-17

### Added

- `SmsResult`, returned by every send: `accepted`/`failed()`, the gateway's `messageId`, its `errorCode` and `errorMessage` for a rejection, and the raw `response`. Each built-in driver reads these from its gateway's documented response.
- `sms.timeout` (`SMS_TIMEOUT`, default 10 seconds) applied to every HTTP driver, so a hanging gateway can't block a request or queue worker.
- `message_id_path` option for the generic `http` driver.
- `SmsSent` and `SmsFailed` events, dispatched once per recipient on every send path: `Sms::send()`, `Sms::sendMany()`, `Sms::driver('name')->send()`, custom `extend()` drivers and the `sms` notification channel. Both carry the `SmsResult`; `SmsFailed` carries the exception instead when the driver threw.
- `SmsSender`, the wrapper the manager now puts around every resolved driver to dispatch those events. `getName()` and `getDriver()` expose the driver name and the underlying `SmsDriver`.
- `SmsSegments` helper (`count()`, `length()`, `isUnicode()`) for working out how many billable parts a message splits into: 160/153 GSM-7 characters or 70/67 Unicode units, with GSM-7 extension characters and emoji counted as two. Also available as `SmsMessage::segments()` and `SmsSent::segments()`.
- `Sms::fake()->fail(?Closure $callback = null)` to simulate gateway rejections in tests, and `SmsFake::driver()` so code that picks a gateway runs against the fake.

### Changed

- **Breaking:** `SmsDriver::send()`, `Sms::send()` and `SmsSender::send()` return `SmsResult` instead of `bool`, and `sendMany()` returns `array<string, SmsResult>`. Custom drivers must return `SmsResult::accepted()` or `SmsResult::rejected()`.
- **Breaking:** drivers no longer write `Log::warning()` on rejections or failed balance checks. Listen for `SmsFailed` to log rejections yourself; `balance()` still returns `null` when unavailable.
- Alpha SMS now treats a response with no `error` code (e.g. an HTML error page) as a rejection; it was previously reported as sent.
- **Breaking:** the `sms` notification channel throws `CouldNotSendSms` when the gateway rejects a message, instead of passing silently. Queued notifications now fail and retry.
- **Breaking:** `Sms::driver()` returns an `SmsSender` instead of the concrete driver. Use `->getDriver()` to reach the concrete driver.
- **Breaking:** a custom driver registered with `extend()` must implement `SmsDriver`; anything else throws `InvalidArgumentException` when resolved.
- **Breaking:** dropped Laravel 11. v2 requires Laravel 12 or 13.

### Upgrading from v1

- Upgrade the app to Laravel 12 or 13 first.
- Catch `CouldNotSendSms` around `notifyNow()` calls that should not fail the request, or let queued notifications retry.
- Replace `if (Sms::send(...))` with `if (Sms::send(...)->accepted)`, and update custom drivers to return `SmsResult`.
- Add an `SmsFailed` listener if you relied on the drivers' log warnings.
- Replace `Sms::driver('x') instanceof SomeDriver` checks with `Sms::driver('x')->getDriver() instanceof SomeDriver`.
- If you wrapped `SmsDriver` yourself to track usage, listen for `SmsSent` instead.

## v1.0.0 - Initial release

### Added

- Driver-style SMS sending via the `Sms` facade, with drivers for `log`, BulkSMSBD, MimSMS, SSL Wireless, Alpha SMS, GreenWeb BD, and a generic config-only HTTP driver for any other REST gateway.
- `sms` notification channel — implement `toSms()` on any notification to route it through the configured gateway.
- `Sms::sendMany(array $to, string $message)` to send the same message to multiple recipients, returning per-recipient results.
- `Sms::fake()` and `AmdadulHaq\BdSms\Testing\SmsFake` for asserting on sent SMS in tests (`assertSent`, `assertNotSent`, `assertSentTo`, `assertNothingSent`, `assertSentCount`), without dispatching anything or hitting the network.
- `Sms::extend()` for registering custom drivers, alongside the generic `http` driver for zero-code REST gateway integration.
- Test suite (Pest + Orchestra Testbench) covering `sendMany`, `Sms::fake()`, and notification-channel integration.
- Tooling: Pint (code style), Larastan (static analysis, level 8), Rector (automated refactoring, targeting Laravel up to 13.0 without attribute-only rewrites since Laravel 11 is still supported).
- CI workflows: test matrix across PHP 8.2-8.5 and Laravel 11/12/13, PHPStan, Pint auto-fix, auto-release, and changelog update.
- Project scaffolding: `.gitignore`, `.gitattributes`, `.editorconfig`, `README.md`, `LICENSE.md`, `CONTRIBUTING.md`.
