<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 15, 220);
        $deliveryFee = 0;

        return [
            'public_number' => sprintf(
                '%s-%s-%s',
                config('bendito.orders.number_prefix', 'BO'),
                now()->format('ymd'),
                Str::upper(Str::random(6)),
            ),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->numerify('119########'),
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $subtotal + $deliveryFee,
            'status' => OrderStatus::Pending,
            'payment_status' => PaymentStatus::Pending,
            'notes' => fake()->boolean(30) ? fake()->sentence() : null,
            'ip_address' => fake()->ipv4(),
        ];
    }

    /** Paid and confirmed, the way a webhook would have left it. */
    public function paid(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Confirmed,
            'payment_status' => PaymentStatus::Approved,
            'paid_at' => fake()->dateTimeBetween('-20 days', 'now'),
            'gateway_payment_id' => (string) fake()->numberBetween(10_000_000_000, 99_999_999_999),
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (): array => ['status' => $status]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }
}
