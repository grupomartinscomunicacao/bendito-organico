<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Contracts\Session\Session;

/**
 * Holds the single product a visitor is about to buy.
 *
 * Only the product id and the quantity are kept. Prices are never stored in
 * the session — they are read from the database again at every step, so a
 * tampered session can at worst change *what* is bought, never *for how much*.
 */
class CheckoutSession
{
    private const KEY = 'checkout.item';

    public function __construct(private readonly Session $session) {}

    public function put(Product $product, float $quantity): void
    {
        $this->session->put(self::KEY, [
            'product_id' => $product->id,
            'quantity' => $quantity,
        ]);
    }

    public function has(): bool
    {
        return $this->session->has(self::KEY);
    }

    /** @return array{product_id: int, quantity: float}|null */
    public function raw(): ?array
    {
        $item = $this->session->get(self::KEY);

        if (! is_array($item) || ! isset($item['product_id'], $item['quantity'])) {
            return null;
        }

        return [
            'product_id' => (int) $item['product_id'],
            'quantity' => (float) $item['quantity'],
        ];
    }

    /**
     * Resolves the basket against the live catalog.
     *
     * Returns null when the basket is empty or the product has since been
     * deactivated, deleted or sold out, so callers can bounce the visitor
     * back to the catalog instead of quoting a stale price.
     *
     * @return array{product: Product, quantity: float}|null
     */
    public function resolve(): ?array
    {
        $item = $this->raw();

        if ($item === null) {
            return null;
        }

        $product = Product::query()
            ->active()
            ->whereKey($item['product_id'])
            ->first();

        if ($product === null) {
            return null;
        }

        // Clamp instead of failing: stock may have dropped while the visitor
        // was filling in the form.
        $quantity = min($item['quantity'], $product->maxOrderableQuantity());

        if ($quantity <= 0) {
            return null;
        }

        return ['product' => $product, 'quantity' => $quantity];
    }

    public function forget(): void
    {
        $this->session->forget(self::KEY);
    }
}
