# Changelog

All notable changes to `bd-sms-laravel` will be documented in this file.

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
