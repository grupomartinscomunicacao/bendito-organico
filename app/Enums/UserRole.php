<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    /** Full access, including staff management and settings. */
    case Admin = 'admin';

    /** Day-to-day catalog and order handling. */
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Operator => 'Operador',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Admin => 'primary',
            self::Operator => 'secondary',
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
