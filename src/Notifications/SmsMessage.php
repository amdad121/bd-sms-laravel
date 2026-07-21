<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Notifications;

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

    public function __toString(): string
    {
        return $this->content;
    }
}
