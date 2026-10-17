<?php

declare(strict_types=1);

namespace AmdadulHaq\BdSms\Support;

/**
 * Works out how many billable parts a message is split into. Gateways bill per
 * part, and a part holds 160 GSM-7 characters (153 once concatenated) or
 * 70 UCS-2 code units (67 once concatenated) when any character falls
 * outside GSM-7, e.g. Bangla or emoji.
 */
class SmsSegments
{
    public const int GSM7_SINGLE = 160;

    public const int GSM7_CONCATENATED = 153;

    public const int UNICODE_SINGLE = 70;

    public const int UNICODE_CONCATENATED = 67;

    private const string GSM7_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

    /**
     * Characters sent as an escape sequence, so each takes two GSM-7 slots.
     */
    private const string GSM7_EXTENDED = "^{}\\[~]|€\f";

    public static function count(string $message): int
    {
        $length = self::length($message);

        if ($length === 0) {
            return 0;
        }

        [$single, $concatenated] = self::isUnicode($message)
            ? [self::UNICODE_SINGLE, self::UNICODE_CONCATENATED]
            : [self::GSM7_SINGLE, self::GSM7_CONCATENATED];

        return $length <= $single ? 1 : (int) ceil($length / $concatenated);
    }

    /**
     * The message length in the units its encoding is billed by: GSM-7
     * septets, or UTF-16 code units (an emoji counts as two).
     */
    public static function length(string $message): int
    {
        if (self::isUnicode($message)) {
            return intdiv(strlen(mb_convert_encoding($message, 'UTF-16BE', 'UTF-8')), 2);
        }

        $length = 0;

        foreach (mb_str_split($message) as $character) {
            $length += str_contains(self::GSM7_EXTENDED, $character) ? 2 : 1;
        }

        return $length;
    }

    public static function isUnicode(string $message): bool
    {
        foreach (mb_str_split($message) as $character) {
            if (! str_contains(self::GSM7_BASIC, $character) && ! str_contains(self::GSM7_EXTENDED, $character)) {
                return true;
            }
        }

        return false;
    }
}
