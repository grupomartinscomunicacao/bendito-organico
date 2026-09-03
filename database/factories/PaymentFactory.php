<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'gateway' => 'mercadopago',
            'external_id' => (string) fake()->unique()->numberBetween(10_000_000_000, 99_999_999_999),
            'preference_id' => fake()->uuid(),
            'status' => PaymentStatus::Approved,
            'gateway_status' => 'approved',
            'gateway_status_detail' => 'accredited',
            'amount' => fake()->randomFloat(2, 15, 220),
            'currency' => 'BRL',
            // A loja aceita só Pix e cartão, então os dados de demonstração
            // não inventam boleto.
            'payment_method' => fake()->randomElement(['pix', 'master', 'visa', 'elo']),
            'payment_type' => fake()->randomElement(['bank_transfer', 'credit_card', 'debit_card']),
            'installments' => 1,
            'payer_email' => fake()->safeEmail(),
            'approved_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => PaymentStatus::Pending,
            'gateway_status' => 'pending',
            'gateway_status_detail' => 'pending_waiting_transfer',
            'approved_at' => null,
        ]);
    }
}
