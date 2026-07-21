<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Testing;

use AmdadulHaq\BdSms\Contracts\SmsDriver;
use Closure;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert as PHPUnit;

/**
 * Swapped in for the real Sms manager via Sms::fake(), recording every send
 * instead of dispatching it so tests can assert on what would have been sent.
 */
class SmsFake implements SmsDriver
{
    /**
     * @var array<int, array{to: string, message: string}>
     */
    private array $sent = [];

    public function send(string $to, string $message): bool
    {
        $this->sent[] = ['to' => $to, 'message' => $message];

        return true;
    }

    /**
     * @param  array<int, string>  $to
     * @return array<string, bool>
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
        return null;
    }

    /**
     * Assert an SMS matching the given truth test was sent.
     */
    public function assertSent(?Closure $callback = null, ?int $times = null): void
    {
        $matching = $this->matching($callback);

        if ($times !== null) {
            PHPUnit::assertCount($times, $matching, sprintf('The expected SMS was sent %d times instead of %d times.', $matching->count(), $times));

            return;
        }

        PHPUnit::assertTrue($matching->isNotEmpty(), 'The expected SMS was not sent.');
    }

    /**
     * Assert no SMS matching the given truth test was sent.
     */
    public function assertNotSent(?Closure $callback = null): void
    {
        PHPUnit::assertTrue($this->matching($callback)->isEmpty(), 'An SMS matching the given constraints was sent.');
    }

    /**
     * Assert an SMS was sent to the given recipient.
     */
    public function assertSentTo(string $to, ?Closure $callback = null): void
    {
        $this->assertSent(fn (string $sentTo, string $message): bool => $sentTo === $to && (! $callback instanceof Closure || $callback($sentTo, $message)));
    }

    /**
     * Assert no SMS were sent at all.
     */
    public function assertNothingSent(): void
    {
        PHPUnit::assertEmpty($this->sent, 'SMS were sent unexpectedly.');
    }

    /**
     * Assert the total number of SMS sent.
     */
    public function assertSentCount(int $count): void
    {
        PHPUnit::assertCount($count, $this->sent, sprintf('Expected %d SMS to be sent, %d were sent.', $count, $this->count()));
    }

    public function count(): int
    {
        return count($this->sent);
    }

    /**
     * @return Collection<int, array{to: string, message: string}>
     */
    private function matching(?Closure $callback): Collection
    {
        $callback ??= fn (): bool => true;

        return collect($this->sent)->filter(
            fn (array $sms): bool => $callback($sms['to'], $sms['message'])
        );
    }
}
