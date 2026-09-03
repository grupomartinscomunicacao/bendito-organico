<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductUnit;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * A line of a placed order. Every product field is a snapshot taken at
 * purchase time so later catalog edits cannot rewrite order history.
 *
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string $product_slug
 * @property ProductUnit $product_unit
 * @property string $unit_price
 * @property string $quantity
 * @property string $subtotal
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_slug',
        'product_unit',
        'product_image',
        'unit_price',
        'quantity',
        'subtotal',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:3',
            'subtotal' => 'decimal:2',
            'product_unit' => ProductUnit::class,
        ];
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** @return BelongsTo<Product, $this> */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (blank($this->product_image)) {
                return asset('images/brand/product-placeholder.png');
            }

            if (str_starts_with($this->product_image, 'images/')) {
                return asset($this->product_image);
            }

            return Storage::disk('public')->url($this->product_image);
        });
    }

    /**
     * "2" for whole amounts, "1,5" when produce was sold by weight.
     */
    protected function formattedQuantity(): Attribute
    {
        return Attribute::get(function (): string {
            $quantity = (float) $this->quantity;

            return floor($quantity) === $quantity
                ? (string) (int) $quantity
                : rtrim(rtrim(number_format($quantity, 3, ',', ''), '0'), ',');
        });
    }
}
