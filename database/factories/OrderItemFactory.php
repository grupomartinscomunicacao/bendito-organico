<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $product = Product::factory();
        $quantity = fake()->randomElement([1, 1, 2, 3, 0.5, 1.5]);
        $unitPrice = fake()->randomFloat(2, 4, 30);

        return [
            'order_id' => Order::factory(),
            'product_id' => $product,
            'product_name' => fake()->words(2, true),
            'product_slug' => fake()->unique()->slug(2),
            'product_unit' => 'un',
            'unit_price' => $unitPrice,
            'quantity' => $quantity,
            'subtotal' => round($unitPrice * $quantity, 2),
        ];
    }

    /** Builds the line from a real product, snapshotting it the way checkout does. */
    public function forProduct(Product $product, float $quantity): static
    {
        return $this->state(fn (): array => [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_slug' => $product->slug,
            'product_unit' => $product->unit,
            'product_image' => $product->image,
            'unit_price' => $product->price,
            'quantity' => $quantity,
            'subtotal' => round((float) $product->price * $quantity, 2),
        ]);
    }
}
