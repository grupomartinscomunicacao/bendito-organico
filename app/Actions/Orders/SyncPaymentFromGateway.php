<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Reconciles one Mercado Pago payment with our own records.
 *
 * The input must always come from a server-side call to the gateway API —
 * never from a redirect, a form field or an unverified webhook body. A
 * customer landing on the success page proves nothing; only this does.
 *
 * The action is idempotent by construction: payments are keyed on
 * (gateway, external_id), and the side effects that must happen exactly once
 * (marking the order paid, decrementing stock) are guarded by the order's own
 * state rather than by how many times a notification arrived.
 */
class SyncPaymentFromGateway
{
    /** Cents of slack allowed when comparing the charged amount to the order. */
    private const AMOUNT_TOLERANCE = 0.01;

    /**
     * @param  array<string, mixed>  $paymentData  Raw /v1/payments/{id} response.
     * @return Payment|null  Null when the payment cannot be matched to an order.
     */
    public function handle(array $paymentData): ?Payment
    {
        $externalId = (string) ($paymentData['id'] ?? '');

        if ($externalId === '') {
            Log::warning('Mercado Pago payment received without an id.');

            return null;
        }

        $order = $this->resolveOrder($paymentData);

        if ($order === null) {
            Log::warning('Mercado Pago payment could not be matched to an order.', [
                'payment_id' => $externalId,
                'external_reference' => Arr::get($paymentData, 'external_reference'),
            ]);

            return null;
        }

        return DB::transaction(function () use ($order, $paymentData, $externalId): Payment {
            // Lock the order so two concurrent notifications cannot both
            // decide they are the one that marks it paid.
            /** @var Order $order */
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            $gatewayStatus = (string) Arr::get($paymentData, 'status', '');
            $status = PaymentStatus::fromMercadoPago($gatewayStatus);
            $amount = (float) Arr::get($paymentData, 'transaction_amount', 0);

            // Guard against an order being settled for less than it is worth.
            // The payment is still recorded — we just refuse to call it paid.
            if ($status === PaymentStatus::Approved && $amount + self::AMOUNT_TOLERANCE < (float) $order->total) {
                Log::critical('Mercado Pago approved an amount lower than the order total.', [
                    'order' => $order->public_number,
                    'order_total' => $order->total,
                    'paid_amount' => $amount,
                    'payment_id' => $externalId,
                ]);

                $status = PaymentStatus::Pending;
            }

            $payment = Payment::updateOrCreate(
                ['gateway' => 'mercadopago', 'external_id' => $externalId],
                [
                    'order_id' => $order->id,
                    'preference_id' => Arr::get($paymentData, 'order.id')
                        ?? $order->gateway_preference_id,
                    'status' => $status,
                    'gateway_status' => $gatewayStatus ?: null,
                    'gateway_status_detail' => Arr::get($paymentData, 'status_detail'),
                    'amount' => $amount,
                    'net_amount' => Arr::get($paymentData, 'transaction_details.net_received_amount'),
                    'currency' => Arr::get($paymentData, 'currency_id', 'BRL'),
                    'payment_method' => Arr::get($paymentData, 'payment_method_id'),
                    'payment_type' => Arr::get($paymentData, 'payment_type_id'),
                    'installments' => Arr::get($paymentData, 'installments'),
                    'payer_email' => Arr::get($paymentData, 'payer.email'),
                    'payload' => $paymentData,
                    'approved_at' => $this->parseDate(Arr::get($paymentData, 'date_approved')),
                ],
            );

            $this->applyToOrder($order, $payment, $status, $externalId);

            return $payment;
        });
    }

    /**
     * Moves the order forward based on the settled payment. Every branch is
     * safe to run twice.
     */
    private function applyToOrder(Order $order, Payment $payment, PaymentStatus $status, string $externalId): void
    {
        $wasPaid = $order->isPaid();

        $order->gateway_payment_id = $externalId;
        $order->payment_status = $status;

        match ($status) {
            PaymentStatus::Approved => $this->markPaid($order),
            PaymentStatus::Refunded => $this->markRefunded($order),
            PaymentStatus::Cancelled => $this->markCancelled($order),
            // A rejected attempt is not the end of the road: the order stays
            // open so the customer can try another payment method.
            PaymentStatus::Rejected, PaymentStatus::Pending => null,
        };

        $order->save();

        if (! $wasPaid && $status === PaymentStatus::Approved) {
            OrderPaid::dispatch($order->fresh(['items', 'address']) ?? $order, $payment);
        }
    }

    private function markPaid(Order $order): void
    {
        if ($order->paid_at !== null) {
            return; // Already settled: do not touch stock again.
        }

        $order->paid_at = now();
        $order->cancelled_at = null;

        if ($order->status === OrderStatus::Pending) {
            $order->status = OrderStatus::Confirmed;
        }

        $this->adjustStock($order, direction: -1);
    }

    private function markRefunded(Order $order): void
    {
        if ($order->status === OrderStatus::Cancelled) {
            return;
        }

        // Money went back, so the goods return to the shelf.
        if ($order->paid_at !== null) {
            $this->adjustStock($order, direction: 1);
        }

        $order->status = OrderStatus::Cancelled;
        $order->cancelled_at = now();
        $order->paid_at = null;
    }

    private function markCancelled(Order $order): void
    {
        if ($order->status->isFinal()) {
            return;
        }

        if ($order->paid_at !== null) {
            $this->adjustStock($order, direction: 1);
            $order->paid_at = null;
        }

        $order->status = OrderStatus::Cancelled;
        $order->cancelled_at = now();
    }

    /**
     * @param  int  $direction  -1 to consume stock, +1 to give it back.
     */
    private function adjustStock(Order $order, int $direction): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            // %.3F pins the literal to plain decimal notation, matching the
            // column scale and never emitting scientific notation into SQL.
            $delta = sprintf('%.3F', abs((float) $item->quantity));
            $operator = $direction < 0 ? '-' : '+';

            // CASE WHEN rather than GREATEST: one atomic statement that reads
            // the same on MySQL, SQLite and Postgres. The clamp stops a
            // concurrent oversell from driving stock negative.
            $expression = "CASE WHEN stock {$operator} {$delta} < 0 THEN 0 ELSE stock {$operator} {$delta} END";

            Product::query()
                ->whereKey($item->product_id)
                ->where('track_stock', true)
                ->update(['stock' => DB::raw($expression)]);
        }
    }

    /**
     * @param  array<string, mixed>  $paymentData
     */
    private function resolveOrder(array $paymentData): ?Order
    {
        // metadata is set by us when the preference is created and survives
        // every gateway round trip, so it is the most reliable pointer.
        $orderId = Arr::get($paymentData, 'metadata.order_id');

        if (filled($orderId)) {
            $order = Order::query()->whereKey((int) $orderId)->first();

            if ($order !== null) {
                return $order;
            }
        }

        $reference = Arr::get($paymentData, 'external_reference');

        if (filled($reference)) {
            return Order::query()->where('public_number', (string) $reference)->first();
        }

        return null;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
