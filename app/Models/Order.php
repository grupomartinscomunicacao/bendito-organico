<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Support\Phone;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $public_number
 * @property string $customer_name
 * @property string $customer_email
 * @property string $customer_phone
 * @property string $subtotal
 * @property string $delivery_fee
 * @property string $total
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property string|null $gateway_preference_id
 * @property string|null $gateway_payment_id
 * @property Carbon|null $paid_at
 * @property-read Address|null $address
 * @property-read \Illuminate\Database\Eloquent\Collection<int, OrderItem> $items
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 */
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'public_number',
        'customer_name',
        'customer_email',
        'customer_phone',
        'subtotal',
        'delivery_fee',
        'total',
        'status',
        'payment_status',
        'gateway_preference_id',
        'gateway_payment_id',
        'notes',
        'internal_notes',
        'paid_at',
        'cancelled_at',
        'ip_address',
        'user_agent',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => OrderStatus::class,
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    /**
     * Public URLs are keyed by the order number, not the auto-increment id,
     * so one customer cannot walk the sequence to read someone else's order.
     */
    public function getRouteKeyName(): string
    {
        return 'public_number';
    }

    /**
     * Human-readable and unguessable: BO-260902-K7X2QF.
     */
    public static function generatePublicNumber(): string
    {
        $prefix = config('bendito.orders.number_prefix', 'BO');

        do {
            $number = sprintf(
                '%s-%s-%s',
                $prefix,
                now()->format('ymd'),
                Str::upper(Str::random(6))
            );
        } while (static::where('public_number', $number)->exists());

        return $number;
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** @return HasOne<Address, $this> */
    public function address(): HasOne
    {
        return $this->hasOne(Address::class);
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasOne<Payment, $this> */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /*
    |--------------------------------------------------------------------------
    | State
    |--------------------------------------------------------------------------
    */

    public function isPaid(): bool
    {
        return $this->payment_status === PaymentStatus::Approved;
    }

    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::Cancelled;
    }

    public function isAwaitingPayment(): bool
    {
        return $this->payment_status === PaymentStatus::Pending && ! $this->isCancelled();
    }

    /** Whether the customer can still be sent back to the gateway to pay. */
    public function isPayable(): bool
    {
        return $this->isAwaitingPayment() && $this->status === OrderStatus::Pending;
    }

    protected function totalQuantity(): Attribute
    {
        return Attribute::get(
            fn (): float => (float) $this->items->sum('quantity')
        );
    }

    /**
     * Phone numbers are stored as bare digits — that is the canonical form and
     * what the gateway and WhatsApp links need. This is the display form:
     * "(11) 98888-7777".
     */
    protected function formattedPhone(): Attribute
    {
        return Attribute::get(
            fn (): string => Phone::format($this->customer_phone)
        );
    }

    protected function customerWhatsappUrl(): Attribute
    {
        return Attribute::get(
            fn (): string => 'https://wa.me/'.Phone::withCountryCode($this->customer_phone)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<self>  $query */
    public function scopePaid(Builder $query): void
    {
        $query->where('payment_status', PaymentStatus::Approved);
    }

    /** @param  Builder<self>  $query */
    public function scopeStatus(Builder $query, OrderStatus|string|null $status): void
    {
        if (blank($status)) {
            return;
        }

        $query->where('status', $status instanceof OrderStatus ? $status->value : $status);
    }

    /** @param  Builder<self>  $query */
    public function scopePaymentStatus(Builder $query, PaymentStatus|string|null $status): void
    {
        if (blank($status)) {
            return;
        }

        $query->where('payment_status', $status instanceof PaymentStatus ? $status->value : $status);
    }

    /** @param  Builder<self>  $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        // Telefones são guardados só com dígitos, então uma busca digitada com
        // máscara ("(11) 98888-7777") precisa perdê-la para encontrar algo.
        $phone = Phone::digits($term) ?: $term;

        $query->where(function (Builder $inner) use ($term, $phone): void {
            $inner->where('public_number', 'like', "%{$term}%")
                ->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhere('customer_email', 'like', "%{$term}%")
                ->orWhere('customer_phone', 'like', "%{$phone}%")
                ->orWhere('gateway_payment_id', 'like', "%{$term}%");
        });
    }

    /**
     * Every order placed with a given phone number, however it was typed.
     *
     * Matching is done on the canonical digits so "(77) 99999-9999" and
     * "77999999999" find the same rows, and on exact values so the
     * customer_phone index still does the work.
     *
     * @param  Builder<self>  $query
     */
    public function scopeForPhone(Builder $query, ?string $phone): void
    {
        $variants = Phone::variants($phone);

        // An empty search must return nothing rather than everything.
        if ($variants === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('customer_phone', $variants);
    }

    /** @param  Builder<self>  $query */
    public function scopePlacedBetween(Builder $query, ?string $from, ?string $to): void
    {
        $query
            ->when($from, fn (Builder $inner) => $inner->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $inner) => $inner->whereDate('created_at', '<=', $to));
    }
}
