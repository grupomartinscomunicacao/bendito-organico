<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\OrderPaid;
use App\Notifications\OrderPaidNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Mails the customer their receipt.
 *
 * Queued and defensive: a mail outage must never bubble back into the webhook
 * and make Mercado Pago retry a notification we already processed correctly.
 */
class SendOrderPaidNotification implements ShouldQueue
{
    public function handle(OrderPaid $event): void
    {
        try {
            Notification::route('mail', $event->order->customer_email)
                ->notify(new OrderPaidNotification($event->order));
        } catch (\Throwable $exception) {
            Log::error('Could not send the order confirmation email.', [
                'order' => $event->order->public_number,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
