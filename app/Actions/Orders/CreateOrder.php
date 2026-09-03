<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Exceptions\CheckoutException;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

/**
 * Turns a validated checkout form into a persisted order.
 *
 * This is the single place where an order comes into existence, and the one
 * place that decides what it costs. The browser supplies who is buying and
 * how much of what; every price is read back from the database under a row
 * lock, so a tampered form, a stale tab or a race between two buyers cannot
 * produce an order that is underpriced or oversold.
 */
class CreateOrder
{
    /**
     * @param  array{
     *     customer_name: string,
     *     customer_email: string,
     *     customer_phone: string,
     *     notes?: string|null,
     *     address: array{
     *         zip_code: string, state: string, city: string, district: string,
     *         street: string, number: string, complement?: string|null, reference?: string|null
     *     },
     *     ip_address?: string|null,
     *     user_agent?: string|null,
     * }  $data
     *
     * @throws CheckoutException
     */
    public function handle(Product $product, float $quantity, array $data): Order
    {
        if ($quantity <= 0) {
            throw CheckoutException::invalidQuantity();
        }

        return DB::transaction(function () use ($product, $quantity, $data): Order {
            // Re-read the product inside the transaction and lock the row.
            // Anything the request carried about this product is discarded.
            $fresh = Product::query()
                ->whereKey($product->getKey())
                ->lockForUpdate()
                ->first();

            if ($fresh === null || ! $fresh->is_active) {
                throw CheckoutException::productUnavailable();
            }

            if (! $fresh->hasStock($quantity)) {
                throw CheckoutException::insufficientStock($fresh);
            }

            $quantity = round($quantity, 3);
            $unitPrice = round((float) $fresh->price, 2);
            $subtotal = round($unitPrice * $quantity, 2);
            $deliveryFee = round((float) config('bendito.checkout.delivery_fee', 0), 2);

            $order = Order::create([
                'public_number' => Order::generatePublicNumber(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'subtotal' => $subtotal,
                'delivery_fee' => $deliveryFee,
                'total' => round($subtotal + $deliveryFee, 2),
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Pending,
                'notes' => $data['notes'] ?? null,
                'ip_address' => $data['ip_address'] ?? null,
                'user_agent' => $data['user_agent'] ?? null,
            ]);

            // Snapshot the product so later catalog edits never rewrite history.
            $order->items()->create([
                'product_id' => $fresh->id,
                'product_name' => $fresh->name,
                'product_slug' => $fresh->slug,
                'product_unit' => $fresh->unit,
                'product_image' => $fresh->image,
                'unit_price' => $unitPrice,
                'quantity' => $quantity,
                'subtotal' => $subtotal,
            ]);

            $order->address()->create($data['address']);

            return $order->load(['items', 'address']);
        });
    }
}
