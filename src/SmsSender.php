<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use AmdadulHaq\BdSms\Events\SmsFailed;
use AmdadulHaq\BdSms\Events\SmsSent;
use Illuminate\Contracts\Events\Dispatcher;
use Throwable;

/**
 * Wraps every driver the manager resolves, built-in or registered through
 * extend(), so SmsSent/SmsFailed fire on every send path, including
 * Sms::driver('name')->send(), without each driver dispatching them.
 */
class SmsSender implements SmsDriver
{
    public function __construct(
        private readonly string $name,
        private readonly SmsDriver $driver,
        private readonly ?Dispatcher $events = null,
    ) {}

    /**
     * @throws Throwable when the driver throws, after dispatching SmsFailed
     */
    public function send(string $to, string $message): SmsResult
    {
        try {
            $result = $this->driver->send($to, $message);
        } catch (Throwable $throwable) {
            $this->events?->dispatch(new SmsFailed($this->name, $to, $message, exception: $throwable));

            throw $throwable;
        }

        $this->events?->dispatch($result->accepted
            ? new SmsSent($this->name, $to, $message, $result)
            : new SmsFailed($this->name, $to, $message, $result));

        return $result;
    }

    /**
     * Send the same message to each recipient individually, returning each result.
     *
     * @param  array<int, string>  $to
     * @return array<string, SmsResult>
     */
    public function sendMany(array $to, string $message): array
    {
        $results = [];

        foreach ($to as $recipient) {
            $results[$recipient] = $this->send($recipient, $message);
        }

        return $results;
    }

    public function balance(): ?float
    {
        return $this->driver->balance();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDriver(): SmsDriver
    {
        return $this->driver;
    }
}
