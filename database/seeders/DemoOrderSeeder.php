<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Sample orders so the admin panel has something to show on a fresh install.
 * Local and staging only — never runs in production.
 */
class DemoOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoOrderSeeder ignorado em produção.');

            return;
        }

        $products = Product::query()->active()->get();

        if ($products->isEmpty()) {
            $this->command?->warn('Nenhum produto cadastrado — rode o ProductSeeder primeiro.');

            return;
        }

        // A spread across the workflow so every badge and filter has data.
        $plan = [
            [OrderStatus::Delivered, true],
            [OrderStatus::Delivered, true],
            [OrderStatus::Shipped, true],
            [OrderStatus::Preparing, true],
            [OrderStatus::Confirmed, true],
            [OrderStatus::Pending, false],
            [OrderStatus::Pending, false],
            [OrderStatus::Cancelled, false],
        ];

        foreach ($plan as [$status, $paid]) {
            $product = $products->random();
            $quantity = fake()->randomElement([1, 1, 2, 3, 1.5]);
            $subtotal = round((float) $product->price * $quantity, 2);

            $factory = Order::factory()->status($status);

            if ($paid) {
                $factory = $factory->paid()->status($status);
            } elseif ($status === OrderStatus::Cancelled) {
                $factory = $factory->cancelled();
            }

            /** @var Order $order */
            $order = $factory->create([
                'subtotal' => $subtotal,
                'total' => $subtotal,
                'created_at' => $created = fake()->dateTimeBetween('-25 days', 'now'),
                'updated_at' => $created,
            ]);

            OrderItem::factory()->forProduct($product, (float) $quantity)->create([
                'order_id' => $order->id,
            ]);

            Address::factory()->create(['order_id' => $order->id]);

            if ($paid) {
                Payment::factory()->create([
                    'order_id' => $order->id,
                    'external_id' => $order->gateway_payment_id,
                    'amount' => $subtotal,
                    'approved_at' => $order->paid_at,
                ]);
            }
        }

        $this->command?->info('8 pedidos de demonstração criados.');
    }
}
