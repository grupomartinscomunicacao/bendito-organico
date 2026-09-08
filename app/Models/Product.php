<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductUnit;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $short_description
 * @property string|null $description
 * @property string $price
 * @property string|null $compare_at_price
 * @property ProductUnit $unit
 * @property string|null $image
 * @property string $stock
 * @property bool $track_stock
 * @property bool $is_active
 * @property bool $is_featured
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'description',
        'price',
        'compare_at_price',
        'unit',
        'image',
        'stock',
        'track_stock',
        'is_active',
        'is_featured',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock' => 'decimal:3',
            'track_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
            'unit' => ProductUnit::class,
        ];
    }

    protected static function booted(): void
    {
        // A slug is part of the public URL, so it is derived here rather than
        // trusted from the form. Uniqueness is enforced by the schema too.
        static::saving(function (self $product): void {
            if (blank($product->slug)) {
                $product->slug = self::uniqueSlug($product->name, $product->id);
            }
        });
    }

    public static function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'produto';
        $slug = $base;
        $suffix = 2;

        while (
            static::withTrashed()
                ->where('slug', $slug)
                ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** @return HasMany<OrderItem, $this> */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Availability
    |--------------------------------------------------------------------------
    */

    public function isAvailable(): bool
    {
        return $this->is_active && $this->hasStock();
    }

    public function hasStock(float $quantity = 0.001): bool
    {
        if (! $this->track_stock) {
            return true;
        }

        return (float) $this->stock >= $quantity;
    }

    /** Largest quantity the storefront will let a customer pick. */
    public function maxOrderableQuantity(): float
    {
        $ceiling = (float) config('bendito.checkout.max_quantity', 99);

        if (! $this->track_stock) {
            return $ceiling;
        }

        return min($ceiling, (float) $this->stock);
    }

    /*
    |--------------------------------------------------------------------------
    | Minimum order
    |--------------------------------------------------------------------------
    |
    | A loja tem um pedido mínimo em reais, e o checkout leva um produto por
    | pedido. Traduzir o mínimo em "quantos deste item" é o que permite abrir
    | a página já com a quantidade certa em vez de deixar o cliente descobrir
    | a regra num erro depois de preencher o formulário inteiro.
    |
    */

    /**
     * Menor quantidade deste produto que alcança o pedido mínimo, arredondada
     * para cima no passo da unidade (não dá para vender 2,4 maços).
     */
    public function minimumOrderQuantity(): float
    {
        $step = $this->unit->step();
        $minimum = (float) config('bendito.checkout.minimum_order', 0);
        $price = (float) $this->price;

        if ($minimum <= 0 || $price <= 0) {
            return $step;
        }

        // O epsilon evita que ruído de ponto flutuante (50 / 12.5 = 4.000000001)
        // empurre o cliente para um passo inteiro a mais do que precisa.
        $needed = $minimum / $price;
        $steps = (int) ceil(($needed / $step) - 1e-9);

        return max($step, round($steps * $step, 3));
    }

    /**
     * Falso quando nem todo o estoque disponível chega ao pedido mínimo — aí
     * o item não pode ser comprado sozinho, e a página do produto diz isso em
     * vez de oferecer um botão que sempre falharia.
     */
    public function canMeetMinimumOrder(): bool
    {
        return $this->minimumOrderQuantity() <= $this->maxOrderableQuantity();
    }

    /** O subtotal deste produto para uma dada quantidade. */
    public function subtotalFor(float $quantity): float
    {
        return round((float) $this->price * $quantity, 2);
    }

    public function meetsMinimumOrder(float $quantity): bool
    {
        $minimum = (float) config('bendito.checkout.minimum_order', 0);

        if ($minimum <= 0) {
            return true;
        }

        // Meio centavo de folga: o subtotal já vem arredondado em 2 casas, e
        // sem a folga um pedido de exatamente R$ 50,00 poderia ser recusado
        // por causa da representação binária do float.
        return $this->subtotalFor($quantity) >= $minimum - 0.005;
    }

    protected function availabilityLabel(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->is_active) {
                return 'Indisponível';
            }

            if (! $this->hasStock()) {
                return 'Esgotado';
            }

            if ($this->track_stock && (float) $this->stock <= 5) {
                return 'Últimas unidades';
            }

            return 'Disponível';
        });
    }

    protected function availabilityVariant(): Attribute
    {
        return Attribute::get(function (): string {
            if (! $this->is_active || ! $this->hasStock()) {
                return 'danger';
            }

            return $this->track_stock && (float) $this->stock <= 5 ? 'accent' : 'success';
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Presentation
    |--------------------------------------------------------------------------
    */

    protected function imageUrl(): Attribute
    {
        return Attribute::get(function (): string {
            if (blank($this->image)) {
                return asset('images/brand/product-placeholder.png');
            }

            // Seeded demo images ship in public/; uploads live on the public disk.
            if (str_starts_with($this->image, 'images/')) {
                return asset($this->image);
            }

            return Storage::disk('public')->url($this->image);
        });
    }

    protected function hasDiscount(): Attribute
    {
        return Attribute::get(
            fn (): bool => $this->compare_at_price !== null
                && (float) $this->compare_at_price > (float) $this->price
        );
    }

    protected function discountPercentage(): Attribute
    {
        return Attribute::get(function (): ?int {
            if (! $this->has_discount) {
                return null;
            }

            $from = (float) $this->compare_at_price;

            return (int) round((($from - (float) $this->price) / $from) * 100);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<self>  $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param  Builder<self>  $query */
    public function scopeFeatured(Builder $query): void
    {
        $query->where('is_featured', true);
    }

    /** @param  Builder<self>  $query */
    public function scopeInStock(Builder $query): void
    {
        $query->where(function (Builder $inner): void {
            $inner->where('track_stock', false)->orWhere('stock', '>', 0);
        });
    }

    /** @param  Builder<self>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    /** @param  Builder<self>  $query */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        $term = trim((string) $term);

        if ($term === '') {
            return;
        }

        $query->where(function (Builder $inner) use ($term): void {
            $inner->where('name', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhere('short_description', 'like', "%{$term}%");
        });
    }
}
