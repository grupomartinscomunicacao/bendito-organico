<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A transaction as reported by the gateway. Rows here are only ever written
 * from data fetched server-side from the Mercado Pago API, never from a
 * browser round trip.
 *
 * @property int $id
 * @property int $order_id
 * @property string $gateway
 * @property string $external_id
 * @property string|null $preference_id
 * @property PaymentStatus $status
 * @property string|null $gateway_status
 * @property string $amount
 * @property array<string, mixed>|null $payload
 */
class Payment extends Model
{
    /** @use HasFactory<\Database\Factories\PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'gateway',
        'external_id',
        'preference_id',
        'status',
        'gateway_status',
        'gateway_status_detail',
        'amount',
        'net_amount',
        'currency',
        'payment_method',
        'payment_type',
        'installments',
        'payer_email',
        'payload',
        'approved_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'installments' => 'integer',
            'status' => PaymentStatus::class,
            'payload' => 'array',
            'approved_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function isApproved(): bool
    {
        return $this->status === PaymentStatus::Approved;
    }

    /** Friendly name for pix / boleto / credit card, straight from the gateway. */
    protected function methodLabel(): Attribute
    {
        return Attribute::get(function (): string {
            return match ($this->payment_type) {
                'credit_card' => 'Cartão de crédito',
                'debit_card' => 'Cartão de débito',
                'bank_transfer' => 'Pix',
                'ticket' => 'Boleto',
                'account_money' => 'Saldo Mercado Pago',
                default => $this->payment_method ?? 'Não informado',
            };
        });
    }

    /** @param  Builder<self>  $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($term): void {
            $inner->where('external_id', 'like', "%{$term}%")
                ->orWhere('preference_id', 'like', "%{$term}%")
                ->orWhere('payer_email', 'like', "%{$term}%")
                ->orWhereHas('order', fn (Builder $order) => $order->where('public_number', 'like', "%{$term}%"));
        });
    }
}
