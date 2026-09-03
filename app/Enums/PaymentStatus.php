<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Approved => 'Aprovado',
            self::Rejected => 'Rejeitado',
            self::Cancelled => 'Cancelado',
            self::Refunded => 'Estornado',
        };
    }

    public function variant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'success',
            self::Rejected, self::Cancelled => 'danger',
            self::Refunded => 'secondary',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock-history',
            self::Approved => 'patch-check',
            self::Rejected => 'x-octagon',
            self::Cancelled => 'slash-circle',
            self::Refunded => 'arrow-counterclockwise',
        };
    }

    public function isSettled(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * Maps a Mercado Pago payment status onto our internal vocabulary.
     *
     * @see https://www.mercadopago.com.br/developers/en/docs/checkout-pro/additional-content/your-integrations/notifications/webhooks
     */
    public static function fromMercadoPago(?string $status): self
    {
        return match ($status) {
            'approved' => self::Approved,
            'rejected' => self::Rejected,
            'cancelled', 'expired' => self::Cancelled,
            'refunded', 'charged_back' => self::Refunded,
            // pending, in_process, in_mediation, authorized and anything
            // unrecognised stay pending: never assume money has moved.
            default => self::Pending,
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
