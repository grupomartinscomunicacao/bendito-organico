<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Ledger of gateway notifications. The unique (source, event_id) index is what
 * makes webhook handling idempotent: Mercado Pago retries a delivery every 15
 * minutes until it receives a 2xx, so the same event lands many times.
 *
 * @property int $id
 * @property string $source
 * @property string $event_id
 * @property string|null $topic
 * @property string|null $action
 * @property string|null $resource_id
 * @property string $status
 * @property array<string, mixed>|null $payload
 */
class WebhookEvent extends Model
{
    public const STATUS_RECEIVED = 'received';

    public const STATUS_PROCESSED = 'processed';

    public const STATUS_IGNORED = 'ignored';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'source',
        'event_id',
        'topic',
        'action',
        'resource_id',
        'status',
        'error',
        'payload',
        'processed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function markProcessed(string $status = self::STATUS_PROCESSED, ?string $error = null): void
    {
        $this->forceFill([
            'status' => $status,
            'error' => $error,
            'processed_at' => now(),
        ])->save();
    }

    public function wasHandled(): bool
    {
        return in_array($this->status, [self::STATUS_PROCESSED, self::STATUS_IGNORED], strict: true);
    }
}
