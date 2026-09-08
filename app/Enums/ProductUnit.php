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
     * A abreviação concordando com a quantidade: "1 maço", "12 maços".
     *
     * Str::plural() é do inglês e devolveria "macos"/"bandejas" por sorte e
     * "duzias" por acidente, então o plural é explícito. As unidades de medida
     * (un, kg, g) são símbolos e não flexionam — "12 kgs" está errado.
     */
    public function abbreviationFor(float $quantity): string
    {
        if (abs($quantity) <= 1) {
            return $this->abbreviation();
        }

        return match ($this) {
            self::Unit, self::Kilogram, self::Gram => $this->abbreviation(),
            self::Bunch => 'maços',
            self::Tray => 'bandejas',
            self::Dozen => 'dúzias',
            self::Package => 'pacotes',
            self::Bundle => 'molhos',
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
