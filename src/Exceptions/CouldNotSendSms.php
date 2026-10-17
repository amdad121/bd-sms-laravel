<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Exceptions;

use AmdadulHaq\BdSms\SmsResult;
use Illuminate\Notifications\Notification;
use RuntimeException;

class CouldNotSendSms extends RuntimeException
{
    public function __construct(string $message, public readonly SmsResult $result)
    {
        parent::__construct($message);
    }

    public static function rejected(Notification $notification, SmsResult $result): self
    {
        $reason = trim(implode(' ', array_filter([$result->errorCode, $result->errorMessage])));

        return new self(
            sprintf('The SMS gateway rejected the [%s] notification%s.', $notification::class, $reason !== '' ? ': '.$reason : ''),
            $result,
        );
    }
}
