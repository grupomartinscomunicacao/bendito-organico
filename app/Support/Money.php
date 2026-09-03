<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Brazilian currency and quantity formatting, in one place so the storefront,
 * the admin and the printed card always agree.
 */
final class Money
{
    /** 1234.5 → "R$ 1.234,50" */
    public static function brl(float|int|string|null $value): string
    {
        return 'R$ '.self::decimal($value);
    }

    /** 1234.5 → "1.234,50" */
    public static function decimal(float|int|string|null $value): string
    {
        return number_format((float) $value, 2, ',', '.');
    }

    /** 1234.5 → "1,2 mil" — compact form for dashboard tiles. */
    public static function compact(float|int|string|null $value): string
    {
        $value = (float) $value;

        return match (true) {
            abs($value) >= 1_000_000 => number_format($value / 1_000_000, 1, ',', '.').' mi',
            abs($value) >= 1_000 => number_format($value / 1_000, 1, ',', '.').' mil',
            default => number_format($value, 0, ',', '.'),
        };
    }

    /**
     * Drops meaningless trailing zeros: 2.000 → "2", 1.500 → "1,5".
     */
    public static function quantity(float|int|string|null $value): string
    {
        $value = (float) $value;

        if (floor($value) === $value) {
            return (string) (int) $value;
        }

        return rtrim(rtrim(number_format($value, 3, ',', '.'), '0'), ',');
    }
}
