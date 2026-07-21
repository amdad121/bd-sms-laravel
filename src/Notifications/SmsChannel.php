<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Notifications;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private readonly SmsDriver $sms) {}

    /**
     * Send the notification's SMS message via the configured gateway.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $to = method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sms', $notification)
            : null;

        $to ??= $notifiable->phone ?? null;

        if (blank($to)) {
            return;
        }

        $message = (string) $notification->toSms($notifiable);

        if (blank($message)) {
            return;
        }

        $this->sms->send($to, $message);
    }
}
