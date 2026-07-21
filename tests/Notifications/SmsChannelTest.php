<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\Notifications\SmsChannel;
use AmdadulHaq\BdSms\Testing\SmsFake;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification
{
    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return 'Welcome!';
    }
}

class EmailOnlyNotification extends Notification
{
    // No toSms() method.
}

class BlankMessageNotification extends Notification
{
    public function toSms(object $notifiable): string
    {
        return '';
    }
}

class PlainRecipient
{
    public function __construct(public ?string $phone = null) {}
}

class RoutedRecipient
{
    use Notifiable;

    public function __construct(private readonly string $smsRoute) {}

    public function routeNotificationForSms(Notification $notification): string
    {
        return $this->smsRoute;
    }
}

it('does nothing when the notification has no toSms method', function (): void {
    $fake = new SmsFake;
    $channel = new SmsChannel($fake);

    $channel->send(new PlainRecipient('01700000000'), new EmailOnlyNotification);

    expect($fake->count())->toBe(0);
});

it('does nothing when there is no recipient to route to', function (): void {
    $fake = new SmsFake;
    $channel = new SmsChannel($fake);

    $channel->send(new PlainRecipient, new WelcomeNotification);

    expect($fake->count())->toBe(0);
});

it('does nothing when the rendered message is blank', function (): void {
    $fake = new SmsFake;
    $channel = new SmsChannel($fake);

    $channel->send(new PlainRecipient('01700000000'), new BlankMessageNotification);

    expect($fake->count())->toBe(0);
});

it('falls back to the phone property when there is no routeNotificationFor method', function (): void {
    $fake = new SmsFake;
    $channel = new SmsChannel($fake);

    $channel->send(new PlainRecipient('01700000000'), new WelcomeNotification);

    $fake->assertSentTo('01700000000', fn (string $to, string $message): bool => $message === 'Welcome!');
});

it('prefers routeNotificationForSms over the phone property', function (): void {
    Sms::fake();

    $recipient = new RoutedRecipient('018-000-0000');
    $recipient->notify(new WelcomeNotification);

    Sms::assertSentTo('018-000-0000');
});
