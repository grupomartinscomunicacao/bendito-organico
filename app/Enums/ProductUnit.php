<?php

declare(strict_types=1);

namespace App\Enums;

enum ProductUnit: string
{
    case Unit = 'un';
    case Bunch = 'maco';
    case Kilogram = 'kg';
    case Gram = 'g';
    case Tray = 'bandeja';
    case Dozen = 'duzia';
    case Package = 'pacote';
    case Bundle = 'molho';

    public function label(): string
    {
        return match ($this) {
            self::Unit => 'Unidade',
            self::Bunch => 'Maço',
            self::Kilogram => 'Quilograma',
            self::Gram => 'Grama',
            self::Tray => 'Bandeja',
            self::Dozen => 'Dúzia',
            self::Package => 'Pacote',
            self::Bundle => 'Molho',
        };
    }

    /** Compact form shown next to prices, e.g. "R$ 6,90 / maço". */
    public function abbreviation(): string
    {
        return match ($this) {
            self::Unit => 'un',
            self::Bunch => 'maço',
            self::Kilogram => 'kg',
            self::Gram => 'g',
            self::Tray => 'bandeja',
            self::Dozen => 'dúzia',
            self::Package => 'pacote',
            self::Bundle => 'molho',
        };
    }

    /**
     * Smallest increment a customer can order.
     *
     * Discrete items go one at a time; produce sold by weight can be bought
     * in half kilos, and herbs by the 50 g.
     */
    public function step(): float
    {
        return match ($this) {
            self::Kilogram => 0.5,
            self::Gram => 50,
            default => 1,
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
