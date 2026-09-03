<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Moves an order along the fulfilment workflow on behalf of an operator.
 *
 * The legal transitions live on OrderStatus, so the rule is enforced here
 * regardless of what the form posted — a stale admin tab cannot skip an order
 * straight from "pendente" to "entregue".
 */
class UpdateOrderStatus
{
    /**
     * @throws ValidationException
     */
    public function handle(Order $order, OrderStatus $target, ?string $internalNotes = null): Order
    {
        if ($order->status === $target) {
            if ($internalNotes !== null) {
                $order->forceFill(['internal_notes' => $internalNotes])->save();
            }

            return $order;
        }

        if (! $order->status->canTransitionTo($target)) {
            throw ValidationException::withMessages([
                'status' => sprintf(
                    'Não é possível mudar o pedido de "%s" para "%s".',
                    $order->status->label(),
                    $target->label(),
                ),
            ]);
        }

        return DB::transaction(function () use ($order, $target, $internalNotes): Order {
            /** @var Order $order */
            $order = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            // Cancelling a paid order puts the goods back on the shelf.
            if ($target === OrderStatus::Cancelled && $order->paid_at !== null) {
                $this->restoreStock($order);
            }

            $order->status = $target;
            $order->cancelled_at = $target === OrderStatus::Cancelled ? now() : null;

            if ($internalNotes !== null) {
                $order->internal_notes = $internalNotes;
            }

            $order->save();

            return $order;
        });
    }

    private function restoreStock(Order $order): void
    {
        $order->loadMissing('items');

        foreach ($order->items as $item) {
            if ($item->product_id === null) {
                continue;
            }

            Product::query()
                ->whereKey($item->product_id)
                ->where('track_stock', true)
                ->update([
                    'stock' => DB::raw(sprintf('stock + %.3F', abs((float) $item->quantity))),
                ]);
        }
    }
}
