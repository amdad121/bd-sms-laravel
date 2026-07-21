<?php

declare(strict_types=1);

use AmdadulHaq\BdSms\Facades\Sms;
use AmdadulHaq\BdSms\Notifications\SmsChannel;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class OtpNotification extends Notification
{
    public function __construct(private readonly string $code) {}

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms(object $notifiable): string
    {
        return 'Your code is '.$this->code;
    }
}

class Customer
{
    use Notifiable;

    public function __construct(public string $phone) {}
}

it('routes notifications through the sms channel and records them on the fake', function (): void {
    Sms::fake();

    $customer = new Customer('017-555-0100');
    $customer->notify(new OtpNotification('4242'));

    Sms::assertSentTo('017-555-0100', fn (string $to, string $message): bool => $message === 'Your code is 4242');
});
