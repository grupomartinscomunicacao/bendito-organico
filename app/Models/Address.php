<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $zip_code
 * @property string $state
 * @property string $city
 * @property string $district
 * @property string $street
 * @property string $number
 * @property string|null $complement
 * @property string|null $reference
 */
class Address extends Model
{
    /** @use HasFactory<\Database\Factories\AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'zip_code',
        'state',
        'city',
        'district',
        'street',
        'number',
        'complement',
        'reference',
    ];

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** "Rua das Flores, 120 — Apto 21" */
    protected function line1(): Attribute
    {
        return Attribute::get(function (): string {
            $line = "{$this->street}, {$this->number}";

            return filled($this->complement) ? "{$line} — {$this->complement}" : $line;
        });
    }

    /** "Centro — São Paulo/SP · 01001-000" */
    protected function line2(): Attribute
    {
        return Attribute::get(
            fn (): string => "{$this->district} — {$this->city}/{$this->state} · {$this->zip_code}"
        );
    }

    protected function fullAddress(): Attribute
    {
        return Attribute::get(fn (): string => "{$this->line1} · {$this->line2}");
    }
}
