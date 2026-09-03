<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContactMessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param  array{name: string, email: string, phone?: string|null, message: string}  $data */
    public function __construct(private readonly array $data) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nova mensagem pelo site — '.$this->data['name'])
            // Replying goes straight back to the visitor, not to the mailbox
            // the site sends from.
            ->replyTo($this->data['email'], $this->data['name'])
            ->greeting('Nova mensagem de contato')
            ->line('**Nome:** '.$this->data['name'])
            ->line('**E-mail:** '.$this->data['email'])
            ->line('**Telefone:** '.($this->data['phone'] ?: 'não informado'))
            ->line('---')
            ->line($this->data['message']);
    }
}
