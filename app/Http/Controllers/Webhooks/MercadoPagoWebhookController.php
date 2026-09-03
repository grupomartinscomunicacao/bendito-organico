<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Actions\Orders\SyncPaymentFromGateway;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\MercadoPagoService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Receives Mercado Pago notifications.
 *
 * Three rules govern everything here:
 *
 *  1. The body is untrusted. It says which resource changed and nothing more;
 *     the resource itself is then fetched from the API over an authenticated
 *     connection, and only that response is allowed to move money.
 *  2. Every delivery is signed, and an unsigned or badly signed one is dropped.
 *  3. Deliveries repeat. Mercado Pago retries every 15 minutes until it sees a
 *     2xx, so processing is idempotent and each delivery is logged.
 *
 * Status codes matter: 2xx stops the retries, anything else asks for another
 * attempt. So a transient failure returns 5xx on purpose, while a message we
 * understand but do not care about returns 200.
 */
class MercadoPagoWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoService $gateway,
        SyncPaymentFromGateway $syncPayment,
    ): JsonResponse {
        $topic = $this->topic($request);
        $resourceId = $this->resourceId($request);

        if (config('mercadopago.verify_signature') && ! $this->hasValidSignature($request, $gateway, $resourceId)) {
            Log::warning('Rejected a Mercado Pago notification with an invalid signature.', [
                'topic' => $topic,
                'resource_id' => $resourceId,
                'ip' => $request->ip(),
            ]);

            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        if ($resourceId === null) {
            return response()->json(['message' => 'Ignored.'], 200);
        }

        $event = $this->recordEvent($request, $topic, $resourceId);

        // Already handled: this is one of the scheduled retries.
        if ($event === null || $event->wasHandled()) {
            return response()->json(['message' => 'Already processed.'], 200);
        }

        try {
            $handled = match ($topic) {
                'payment' => $this->handlePayment($gateway, $syncPayment, $resourceId),
                'merchant_order' => $this->handleMerchantOrder($gateway, $syncPayment, $resourceId),
                default => false,
            };

            $event->markProcessed(
                $handled
                    ? WebhookEvent::STATUS_PROCESSED
                    : WebhookEvent::STATUS_IGNORED
            );

            return response()->json(['message' => 'OK'], 200);
        } catch (PaymentGatewayException $exception) {
            // The gateway is unreachable or erroring. Ask for a retry.
            $event->markProcessed(WebhookEvent::STATUS_FAILED, $exception->getMessage());

            Log::error('Mercado Pago webhook could not reach the API.', [
                'topic' => $topic,
                'resource_id' => $resourceId,
                'message' => $exception->getMessage(),
            ]);

            return response()->json(['message' => 'Gateway unavailable.'], 503);
        } catch (\Throwable $exception) {
            $event->markProcessed(WebhookEvent::STATUS_FAILED, $exception->getMessage());

            Log::error('Mercado Pago webhook failed.', [
                'topic' => $topic,
                'resource_id' => $resourceId,
                'exception' => $exception,
            ]);

            return response()->json(['message' => 'Processing error.'], 500);
        }
    }

    /**
     * @return bool  True when a payment was actually reconciled against an
     *               order, false when there was nothing to do.
     */
    private function handlePayment(
        MercadoPagoService $gateway,
        SyncPaymentFromGateway $syncPayment,
        string $paymentId,
    ): bool {
        $payment = $gateway->getPayment($paymentId);

        if ($payment === null) {
            Log::warning('Mercado Pago notified a payment that the API does not know about.', [
                'payment_id' => $paymentId,
            ]);

            return false;
        }

        return $syncPayment->handle($payment) !== null;
    }

    /**
     * Merchant order notifications carry the payments attached to a checkout
     * rather than a payment itself.
     */
    private function handleMerchantOrder(
        MercadoPagoService $gateway,
        SyncPaymentFromGateway $syncPayment,
        string $merchantOrderId,
    ): bool {
        $merchantOrder = $gateway->getMerchantOrder($merchantOrderId);

        if ($merchantOrder === null) {
            return false;
        }

        $handled = false;

        foreach ((array) ($merchantOrder['payments'] ?? []) as $payment) {
            $paymentId = (string) ($payment['id'] ?? '');

            if ($paymentId === '') {
                continue;
            }

            if ($this->handlePayment($gateway, $syncPayment, $paymentId)) {
                $handled = true;
            }
        }

        return $handled;
    }

    private function hasValidSignature(Request $request, MercadoPagoService $gateway, ?string $resourceId): bool
    {
        return $gateway->verifySignature(
            signatureHeader: $request->header('x-signature'),
            requestId: $request->header('x-request-id'),
            dataId: $resourceId,
        );
    }

    /**
     * Mercado Pago uses "type" on the JSON body and "topic" on the query
     * string depending on the notification flavour.
     */
    private function topic(Request $request): ?string
    {
        $topic = $request->input('type')
            ?? $request->input('topic')
            ?? $request->query('type')
            ?? $request->query('topic');

        return is_string($topic) ? Str::lower(trim($topic)) : null;
    }

    /**
     * The changed resource: `data.id` on the body, `data.id` or `id` on the
     * query string.
     */
    private function resourceId(Request $request): ?string
    {
        $candidates = [
            $request->input('data.id'),
            $request->query('data_id'),
            $request->query('id'),
            $request->input('resource'),
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                // merchant_order notifications send a full resource URL.
                $value = trim((string) $candidate);

                return Str::afterLast($value, '/');
            }
        }

        // Some legacy deliveries put data.id in the query string literally.
        $queryDataId = $request->query('data.id');

        return is_scalar($queryDataId) && trim((string) $queryDataId) !== ''
            ? trim((string) $queryDataId)
            : null;
    }

    /**
     * Writes the delivery to the ledger. Returns null when a concurrent
     * request has already claimed this delivery id.
     */
    private function recordEvent(Request $request, ?string $topic, string $resourceId): ?WebhookEvent
    {
        // x-request-id is unique per delivery and stable across retries, which
        // is exactly the identity we need. Fall back to the resource when the
        // header is absent so the ledger never loses an event.
        $eventId = $request->header('x-request-id')
            ?: sprintf('%s:%s:%s', $topic ?? 'unknown', $resourceId, $request->input('action', ''));

        try {
            return WebhookEvent::firstOrCreate(
                ['source' => 'mercadopago', 'event_id' => Str::limit($eventId, 120, '')],
                [
                    'topic' => $topic,
                    'action' => $request->input('action'),
                    'resource_id' => $resourceId,
                    'status' => WebhookEvent::STATUS_RECEIVED,
                    'payload' => $request->all(),
                ],
            );
        } catch (QueryException $exception) {
            // Unique violation: another worker took this delivery first.
            Log::info('Duplicate Mercado Pago delivery ignored.', ['event_id' => $eventId]);

            return null;
        }
    }
}
