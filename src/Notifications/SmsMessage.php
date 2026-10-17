<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Notifications;

use AmdadulHaq\BdSms\Support\SmsSegments;
use Stringable;

class SmsMessage implements Stringable
{
    protected string $content = '';

    public static function create(string $content = ''): self
    {
        return (new self)->content($content);
    }

    public function content(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function segments(): int
    {
        return SmsSegments::count($this->content);
    }

    public function __toString(): string
    {
        return $this->content;
    }
}
