<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Exceptions\CouldNotSendSms;
use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\Notifications\SmsChannel;
use AmdadulHaq\BdSms\Testing\SmsFake;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Event;

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

it('throws when the gateway rejects the message', function (): void {
    $channel = new SmsChannel((new SmsFake)->fail());

    expect(fn () => $channel->send(new PlainRecipient('01700000000'), new WelcomeNotification))
        ->toThrow(CouldNotSendSms::class, 'The SMS gateway rejected the [WelcomeNotification] notification: fake Rejected by Sms::fake()->fail().');
});

it('reports a rejected notification as failed rather than sent', function (): void {
    Sms::fake()->fail();
    Event::fake([NotificationSent::class, NotificationFailed::class]);

    expect(fn () => (new RoutedRecipient('01700000000'))->notify(new WelcomeNotification))
        ->toThrow(CouldNotSendSms::class);

    Event::assertDispatched(NotificationFailed::class);
    Event::assertNotDispatched(NotificationSent::class);
});
