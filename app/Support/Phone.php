<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Brazilian phone numbers, in one place.
 *
 * The canonical stored form is bare national digits — "11988887777" — because
 * that is what an index can match and what the gateway and WhatsApp links
 * expect. Customers, however, type the same number in half a dozen shapes:
 * "(77) 99999-9999", "77 99999-9999", "+55 77 99999-9999". Everything that
 * writes or reads a phone goes through here so those all collapse to one
 * value and a lookup never misses because of punctuation.
 */
final class Phone
{
    /** Every digit the customer typed, nothing else. */
    public static function digits(?string $value): string
    {
        return preg_replace('/\D/', '', (string) $value) ?? '';
    }

    /**
     * The canonical form: national digits, no mask, no country code.
     *
     * The "55" prefix is only dropped when what remains is still a plausible
     * national number, so a São Paulo landline like "5512345678" — which
     * legitimately starts with 55 — is left alone.
     */
    public static function normalize(?string $value): string
    {
        $digits = self::digits($value);

        if (str_starts_with($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    /** Display form: "(11) 98888-7777". Unrecognised input is echoed back. */
    public static function format(?string $value): string
    {
        $digits = self::normalize($value);

        return match (strlen($digits)) {
            11 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7)),
            10 => sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6)),
            default => (string) $value,
        };
    }

    /** International form for wa.me links: "5511988887777". */
    public static function withCountryCode(?string $value): string
    {
        $digits = self::normalize($value);

        return $digits === '' ? '' : '55'.$digits;
    }

    /**
     * The stored values that should be treated as the same number.
     *
     * New orders and the normalising migration both write the bare national
     * form, but a row created by hand or imported from elsewhere may still
     * carry the country code — matching both keeps the lookup honest without
     * giving up the index.
     *
     * @return array<int, string>
     */
    public static function variants(?string $value): array
    {
        $digits = self::normalize($value);

        if ($digits === '') {
            return [];
        }

        return array_values(array_unique([$digits, '55'.$digits]));
    }
}
