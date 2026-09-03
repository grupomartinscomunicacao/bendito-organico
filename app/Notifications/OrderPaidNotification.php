<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Receipt sent to the customer once payment clears.
 */
class OrderPaidNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Order $order) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->loadMissing('items');

        $message = (new MailMessage)
            ->subject("Pagamento confirmado — pedido {$order->public_number}")
            ->greeting("Olá, {$order->customer_name}!")
            ->line('Recebemos a confirmação do seu pagamento. Já estamos separando seus orgânicos. 🌱')
            ->line("**Pedido:** {$order->public_number}");

        foreach ($order->items as $item) {
            $message->line(sprintf(
                '• %s — %s %s × %s',
                $item->product_name,
                $item->formatted_quantity,
                $item->product_unit->abbreviation(),
                Money::brl($item->unit_price),
            ));
        }

        return $message
            ->line('**Total pago:** '.Money::brl($order->total))
            ->action('Acompanhar meu pedido', route('orders.show', $order))
            ->line(config('bendito.checkout.delivery_notice'))
            ->salutation('Com carinho, equipe '.config('bendito.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'public_number' => $this->order->public_number,
            'total' => $this->order->total,
        ];
    }
}
