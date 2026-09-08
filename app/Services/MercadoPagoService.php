<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Thin, explicit client for the Mercado Pago REST API.
 *
 * Talking to the documented endpoints over Laravel's HTTP client keeps the
 * dependency surface small and pins the contract to the API itself rather
 * than to an SDK release. Only three endpoints are used:
 *
 *   POST /checkout/preferences   create a Checkout Pro preference
 *   GET  /v1/payments/{id}       authoritative payment state
 *   GET  /merchant_orders/{id}   payment ids behind a merchant order
 *
 * @see https://www.mercadopago.com.br/developers/en/reference/preferences/_checkout_preferences/post
 * @see https://www.mercadopago.com.br/developers/en/reference/payments/_payments_id/get
 */
class MercadoPagoService
{
    public function __construct(
        private readonly ?string $accessToken = null,
        private readonly ?string $webhookSecret = null,
    ) {}

    public static function make(): self
    {
        return new self(
            accessToken: config('mercadopago.access_token'),
            webhookSecret: config('mercadopago.webhook_secret'),
        );
    }

    public function isConfigured(): bool
    {
        return filled($this->accessToken);
    }

    /**
     * Test credentials are prefixed "TEST-" and require the sandbox init point.
     */
    public function isSandbox(): bool
    {
        $override = config('mercadopago.sandbox');

        if ($override !== null && $override !== '') {
            return filter_var($override, FILTER_VALIDATE_BOOLEAN);
        }

        return Str::startsWith((string) $this->accessToken, 'TEST-');
    }

    /*
    |--------------------------------------------------------------------------
    | Checkout
    |--------------------------------------------------------------------------
    */

    /**
     * Creates a Checkout Pro preference for an order and returns the id plus
     * the URL the customer should be sent to.
     *
     * Every monetary value here is read from the persisted order, which was
     * itself priced from the database. Nothing the browser submitted reaches
     * the gateway.
     *
     * @return array{id: string, init_point: string}
     *
     * @throws PaymentGatewayException
     */
    public function createPreference(Order $order): array
    {
        $this->assertConfigured();

        $order->loadMissing(['items', 'address']);

        $response = $this->send(
            // Retrying a create is safe only because this key makes Mercado
            // Pago collapse duplicates into a single preference.
            fn (PendingRequest $request) => $request
                ->withHeader('X-Idempotency-Key', 'pref-'.$order->public_number)
                ->post('/checkout/preferences', $this->preferencePayload($order)),
            '/checkout/preferences',
        );

        $data = $this->decode($response, '/checkout/preferences');

        $initPoint = $this->isSandbox()
            ? ($data['sandbox_init_point'] ?? $data['init_point'] ?? null)
            : ($data['init_point'] ?? null);

        if (blank($data['id'] ?? null) || blank($initPoint)) {
            throw new PaymentGatewayException(
                'Mercado Pago returned a preference without a usable checkout URL.',
                ['order' => $order->public_number, 'response' => $data],
            );
        }

        return [
            'id' => (string) $data['id'],
            'init_point' => (string) $initPoint,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function preferencePayload(Order $order): array
    {
        $payload = [
            'items' => $order->items->map(fn (OrderItem $item) => $this->itemPayload($item))->all(),
            'payer' => $this->payerPayload($order),
            'external_reference' => $order->public_number,
            'statement_descriptor' => config('mercadopago.statement_descriptor'),
            'binary_mode' => (bool) config('mercadopago.binary_mode'),
            'payment_methods' => $this->paymentMethodsPayload(),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->public_number,
            ],
            'back_urls' => [
                'success' => route('payment.return'),
                'pending' => route('payment.return'),
                'failure' => route('payment.return'),
            ],
        ];

        // Hold the checkout open for a bounded window so abandoned attempts do
        // not keep stock reserved forever.
        if ($minutes = (int) config('mercadopago.expires_after_minutes')) {
            $payload['expires'] = true;
            $payload['expiration_date_from'] = now()->toIso8601String();
            $payload['expiration_date_to'] = now()->addMinutes($minutes)->toIso8601String();
        }

        // Mercado Pago rejects both of these when they point at a host it
        // cannot resolve, which is the normal case on a dev machine. Send them
        // only once the app is reachable from the internet.
        if ($notificationUrl = $this->notificationUrl()) {
            $payload['notification_url'] = $notificationUrl;
        }

        if ($this->hasPubliclyReachableUrl()) {
            $payload['auto_return'] = 'approved';
        }

        return $payload;
    }

    /**
     * Restringe o checkout aos meios que a loja aceita.
     *
     * Só o texto da página não basta: sem `excluded_payment_types`, o Checkout
     * Pro segue oferecendo boleto e o cliente consegue escolhê-lo.
     *
     * @return array<string, mixed>
     */
    private function paymentMethodsPayload(): array
    {
        $payload = [
            'installments' => (int) config('mercadopago.installments', 12),
        ];

        $excluded = array_values(array_filter(array_map(
            static fn ($type) => trim((string) $type),
            (array) config('mercadopago.excluded_payment_types', []),
        )));

        if ($excluded !== []) {
            $payload['excluded_payment_types'] = array_map(
                static fn (string $type) => ['id' => $type],
                $excluded,
            );
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function itemPayload(OrderItem $item): array
    {
        $quantity = (float) $item->quantity;
        $unitPrice = (float) $item->unit_price;
        $title = $item->product_name;

        // The API only accepts an integer quantity. Produce sold by weight
        // (1.5 kg) is therefore billed as a single line worth the subtotal,
        // with the real amount spelled out in the title the buyer sees.
        if (floor($quantity) !== $quantity) {
            $title = sprintf(
                '%s (%s %s)',
                $item->product_name,
                $item->formatted_quantity,
                $item->product_unit->abbreviationFor($quantity),
            );
            $unitPrice = (float) $item->subtotal;
            $quantity = 1.0;
        }

        return array_filter([
            'id' => (string) ($item->product_id ?? $item->id),
            'title' => Str::limit($title, 250, ''),
            'description' => Str::limit(
                sprintf('%s — %s', config('bendito.name'), $item->product_unit->label()),
                250,
                ''
            ),
            'category_id' => 'food',
            'quantity' => (int) $quantity,
            'currency_id' => 'BRL',
            'unit_price' => round($unitPrice, 2),
            'picture_url' => $this->hasPubliclyReachableUrl() ? $item->image_url : null,
        ], fn ($value) => $value !== null);
    }

    /**
     * @return array<string, mixed>
     */
    private function payerPayload(Order $order): array
    {
        $name = Str::of($order->customer_name)->squish();
        $digits = preg_replace('/\D/', '', $order->customer_phone) ?? '';

        // Brazilian numbers arrive as DDD + subscriber; drop a leading country
        // code if the customer typed one.
        if (Str::startsWith($digits, '55') && strlen($digits) > 11) {
            $digits = substr($digits, 2);
        }

        $payer = [
            'name' => (string) $name->before(' '),
            'surname' => (string) $name->after(' ')->trim(),
            'email' => $order->customer_email,
        ];

        if (strlen($digits) >= 10) {
            $payer['phone'] = [
                'area_code' => substr($digits, 0, 2),
                'number' => substr($digits, 2),
            ];
        }

        if ($order->address !== null) {
            $payer['address'] = [
                'zip_code' => preg_replace('/\D/', '', $order->address->zip_code),
                'street_name' => $order->address->street,
                'street_number' => $order->address->number,
            ];
        }

        return $payer;
    }

    /*
    |--------------------------------------------------------------------------
    | Reading gateway state
    |--------------------------------------------------------------------------
    */

    /**
     * The authoritative view of a payment. This is the only thing allowed to
     * mark an order paid — never a redirect back from the checkout page.
     *
     * @return array<string, mixed>|null  Null when the payment does not exist.
     *
     * @throws PaymentGatewayException
     */
    public function getPayment(string $paymentId): ?array
    {
        $this->assertConfigured();

        $endpoint = "/v1/payments/{$paymentId}";

        $response = $this->send(fn (PendingRequest $request) => $request->get($endpoint), $endpoint);

        if ($response->status() === 404) {
            return null;
        }

        return $this->decode($response, $endpoint);
    }

    /**
     * Merchant order notifications carry no payment id, only the ids of the
     * payments attached to them.
     *
     * @return array<string, mixed>|null
     *
     * @throws PaymentGatewayException
     */
    public function getMerchantOrder(string $merchantOrderId): ?array
    {
        $this->assertConfigured();

        $endpoint = "/merchant_orders/{$merchantOrderId}";

        $response = $this->send(fn (PendingRequest $request) => $request->get($endpoint), $endpoint);

        if ($response->status() === 404) {
            return null;
        }

        return $this->decode($response, $endpoint);
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook signature
    |--------------------------------------------------------------------------
    */

    /**
     * Verifies the HMAC that Mercado Pago attaches to every notification.
     *
     * The manifest is built from three values and hashed with the webhook
     * secret: `id:<data.id>;request-id:<x-request-id>;ts:<ts>;`
     *
     * @see https://www.mercadopago.com.br/developers/en/docs/your-integrations/notifications/webhooks
     */
    public function verifySignature(?string $signatureHeader, ?string $requestId, ?string $dataId): bool
    {
        if (blank($this->webhookSecret)) {
            Log::warning('Mercado Pago webhook secret is not configured; rejecting notification.');

            return false;
        }

        if (blank($signatureHeader)) {
            return false;
        }

        $parts = [];

        foreach (explode(',', $signatureHeader) as $segment) {
            if (! str_contains($segment, '=')) {
                continue;
            }

            [$key, $value] = explode('=', trim($segment), 2);
            $parts[trim($key)] = trim($value);
        }

        $timestamp = $parts['ts'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (blank($timestamp) || blank($signature)) {
            return false;
        }

        // Reject stale signatures so a captured notification cannot be replayed
        // days later.
        $tolerance = (int) config('mercadopago.signature_tolerance', 300);

        if ($tolerance > 0 && abs(now()->getTimestamp() - (int) $timestamp) > $tolerance) {
            Log::warning('Mercado Pago webhook signature outside the accepted time window.', [
                'ts' => $timestamp,
            ]);

            return false;
        }

        // The docs lowercase alphanumeric ids and omit any segment whose value
        // is absent, so the manifest is assembled piece by piece.
        $manifest = '';

        if (filled($dataId)) {
            $manifest .= 'id:'.(ctype_digit($dataId) ? $dataId : Str::lower($dataId)).';';
        }

        if (filled($requestId)) {
            $manifest .= 'request-id:'.$requestId.';';
        }

        $manifest .= 'ts:'.$timestamp.';';

        $expected = hash_hmac('sha256', $manifest, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    /*
    |--------------------------------------------------------------------------
    | Internals
    |--------------------------------------------------------------------------
    */

    /**
     * Runs a request and turns an unreachable gateway into the same exception
     * type callers already handle, so no raw transport error escapes.
     *
     * @param  callable(PendingRequest): Response  $callback
     *
     * @throws PaymentGatewayException
     */
    private function send(callable $callback, string $endpoint): Response
    {
        try {
            return $callback($this->request());
        } catch (ConnectionException $exception) {
            Log::error('Could not reach Mercado Pago.', [
                'endpoint' => $endpoint,
                'message' => $exception->getMessage(),
            ]);

            throw new PaymentGatewayException(
                "Could not reach Mercado Pago at [{$endpoint}].",
                ['endpoint' => $endpoint],
                previous: $exception,
            );
        }
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('mercadopago.base_url'), '/'))
            ->withToken((string) $this->accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('mercadopago.timeout', 20))
            ->retry(
                times: (int) config('mercadopago.retry_times', 3),
                sleepMilliseconds: (int) config('mercadopago.retry_sleep', 250),
                // Retry transport hiccups and gateway-side faults only; a 4xx
                // means the request itself is wrong and will never succeed.
                when: fn (\Throwable $e, PendingRequest $request) => $e instanceof ConnectionException,
                throw: false,
            );
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PaymentGatewayException
     */
    private function decode(Response $response, string $endpoint): array
    {
        if ($response->failed()) {
            $body = $response->json() ?? ['raw' => Str::limit($response->body(), 500)];

            Log::error('Mercado Pago API error.', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $body,
            ]);

            throw PaymentGatewayException::requestFailed($endpoint, $response->status(), ['body' => $body]);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new PaymentGatewayException("Mercado Pago returned a non-JSON body for [{$endpoint}].");
        }

        return $data;
    }

    /** @throws PaymentGatewayException */
    private function assertConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw PaymentGatewayException::notConfigured();
        }
    }

    private function notificationUrl(): ?string
    {
        $configured = config('mercadopago.webhook_url');

        if (filled($configured)) {
            return (string) $configured;
        }

        return $this->hasPubliclyReachableUrl() ? route('webhooks.mercadopago') : null;
    }

    /**
     * Mercado Pago has to be able to resolve the host, so loopback and
     * private addresses are treated as unreachable.
     */
    private function hasPubliclyReachableUrl(): bool
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        if (in_array($host, ['localhost', '127.0.0.1', '::1', 'host.docker.internal'], strict: true)) {
            return false;
        }

        return ! (bool) preg_match('/^(10\.|192\.168\.|172\.(1[6-9]|2\d|3[01])\.)/', $host)
            && ! str_ends_with($host, '.local')
            && ! str_ends_with($host, '.test');
    }
}
