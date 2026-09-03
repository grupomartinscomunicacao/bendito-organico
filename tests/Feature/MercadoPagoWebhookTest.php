<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderPaid;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * The webhook is the only thing allowed to mark an order paid, so it gets the
 * hardest questions: forged signatures, replays, and payments for the wrong
 * amount.
 */
class MercadoPagoWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    private const REQUEST_ID = 'req-abc-123';

    private Product $product;

    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'price' => 25.00,
            'stock' => 10,
            'track_stock' => true,
        ]);

        $this->order = Order::factory()->create([
            'subtotal' => 50.00,
            'total' => 50.00,
        ]);

        OrderItem::factory()->forProduct($this->product, 2)->create([
            'order_id' => $this->order->id,
        ]);

        Address::factory()->create(['order_id' => $this->order->id]);
    }

    /**
     * Builds the exact HMAC Mercado Pago would send for this delivery.
     *
     * The request id is part of the manifest, so it has to match the header
     * actually sent — signing with a different one is a forgery.
     */
    private function signature(string $dataId, ?int $timestamp = null, ?string $requestId = null): string
    {
        $timestamp ??= now()->getTimestamp();
        $requestId ??= self::REQUEST_ID;

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, self::SECRET);

        return "ts={$timestamp},v1={$hash}";
    }

    /** @param  array<string, mixed>  $overrides */
    private function fakePayment(array $overrides = []): array
    {
        return array_merge([
            'id' => 111222333,
            'status' => 'approved',
            'status_detail' => 'accredited',
            'transaction_amount' => 50.00,
            'currency_id' => 'BRL',
            'payment_method_id' => 'pix',
            'payment_type_id' => 'bank_transfer',
            'external_reference' => $this->order->public_number,
            'date_approved' => now()->toIso8601String(),
            'payer' => ['email' => 'maria@example.com'],
            'metadata' => ['order_id' => $this->order->id],
        ], $overrides);
    }

    /** @param  array<string, mixed>|null  $payment */
    private function notify(string $dataId = '111222333', ?array $payment = null, ?string $signature = null): \Illuminate\Testing\TestResponse
    {
        Http::fake([
            "*/v1/payments/{$dataId}" => Http::response($payment ?? $this->fakePayment()),
        ]);

        return $this->withHeaders([
            'x-signature' => $signature ?? $this->signature($dataId),
            'x-request-id' => self::REQUEST_ID,
        ])->postJson(route('webhooks.mercadopago')."?data.id={$dataId}", [
            'type' => 'payment',
            'action' => 'payment.updated',
            'data' => ['id' => $dataId],
        ]);
    }

    #[Test]
    public function it_marks_the_order_paid_when_the_gateway_confirms_the_payment(): void
    {
        Event::fake([OrderPaid::class]);

        $this->notify()->assertOk();

        $this->order->refresh();

        $this->assertSame(PaymentStatus::Approved, $this->order->payment_status);
        $this->assertSame(OrderStatus::Confirmed, $this->order->status);
        $this->assertNotNull($this->order->paid_at);
        $this->assertSame('111222333', $this->order->gateway_payment_id);

        Event::assertDispatched(OrderPaid::class);
    }

    #[Test]
    public function it_rejects_a_notification_with_a_forged_signature(): void
    {
        $this->notify(signature: 'ts='.now()->getTimestamp().',v1=deadbeef')
            ->assertStatus(401);

        $this->order->refresh();

        $this->assertSame(PaymentStatus::Pending, $this->order->payment_status);
        $this->assertNull($this->order->paid_at);
    }

    #[Test]
    public function it_rejects_a_notification_with_no_signature_at_all(): void
    {
        Http::fake();

        $this->postJson(route('webhooks.mercadopago').'?data.id=111222333', [
            'type' => 'payment',
            'data' => ['id' => '111222333'],
        ])->assertStatus(401);

        $this->assertSame(PaymentStatus::Pending, $this->order->refresh()->payment_status);
    }

    #[Test]
    public function it_rejects_a_replayed_signature_from_outside_the_time_window(): void
    {
        $stale = now()->subHour()->getTimestamp();

        $this->notify(signature: $this->signature('111222333', $stale))
            ->assertStatus(401);

        $this->assertSame(PaymentStatus::Pending, $this->order->refresh()->payment_status);
    }

    #[Test]
    public function it_processes_a_repeated_delivery_only_once(): void
    {
        $this->notify()->assertOk();
        $this->notify()->assertOk();
        $this->notify()->assertOk();

        $this->assertSame(1, Payment::query()->count());
        $this->assertSame(1, WebhookEvent::query()->count());

        // Stock must have been consumed exactly once, not three times.
        $this->assertSame('8.000', $this->product->refresh()->stock);
    }

    #[Test]
    public function it_refuses_to_settle_an_order_paid_for_less_than_it_is_worth(): void
    {
        $this->notify(payment: $this->fakePayment(['transaction_amount' => 1.00]))
            ->assertOk();

        $this->order->refresh();

        // The transaction is recorded, but the order is not treated as paid.
        $this->assertSame(PaymentStatus::Pending, $this->order->payment_status);
        $this->assertNull($this->order->paid_at);
        $this->assertSame('1.00', Payment::query()->firstOrFail()->amount);
    }

    #[Test]
    public function it_leaves_the_order_open_after_a_rejected_payment(): void
    {
        $this->notify(payment: $this->fakePayment(['status' => 'rejected', 'status_detail' => 'cc_rejected_bad_filled_card_number']))
            ->assertOk();

        $this->order->refresh();

        $this->assertSame(PaymentStatus::Rejected, $this->order->payment_status);
        $this->assertSame(OrderStatus::Pending, $this->order->status);
        $this->assertNull($this->order->paid_at);
    }

    #[Test]
    public function it_treats_an_in_process_payment_as_still_pending(): void
    {
        $this->notify(payment: $this->fakePayment(['status' => 'in_process']))->assertOk();

        $this->assertSame(PaymentStatus::Pending, $this->order->refresh()->payment_status);
    }

    #[Test]
    public function it_returns_stock_and_cancels_the_order_when_a_payment_is_refunded(): void
    {
        // A sequence, not two fake() calls: repeated fake() calls merge their
        // stubs and the first match wins, so the second would never be seen.
        Http::fakeSequence('*/v1/payments/111222333')
            ->push($this->fakePayment())
            ->push($this->fakePayment(['status' => 'refunded']));

        $deliver = fn (string $requestId) => $this->withHeaders([
            'x-signature' => $this->signature('111222333', requestId: $requestId),
            'x-request-id' => $requestId,
        ])->postJson(route('webhooks.mercadopago').'?data.id=111222333', [
            'type' => 'payment',
            'action' => 'payment.updated',
            'data' => ['id' => '111222333'],
        ]);

        $deliver(self::REQUEST_ID)->assertOk();

        $this->assertSame('8.000', $this->product->refresh()->stock);
        $this->assertSame(PaymentStatus::Approved, $this->order->refresh()->payment_status);

        // The refund arrives as its own delivery, with its own request id.
        $deliver('req-refund-999')->assertOk();

        $this->order->refresh();

        $this->assertSame(PaymentStatus::Refunded, $this->order->payment_status);
        $this->assertSame(OrderStatus::Cancelled, $this->order->status);
        $this->assertSame('10.000', $this->product->refresh()->stock);
    }

    #[Test]
    public function it_asks_for_a_retry_when_the_gateway_api_is_down(): void
    {
        Http::fake(['*/v1/payments/*' => Http::response(['message' => 'server error'], 500)]);

        $this->withHeaders([
            'x-signature' => $this->signature('111222333'),
            'x-request-id' => self::REQUEST_ID,
        ])->postJson(route('webhooks.mercadopago').'?data.id=111222333', [
            'type' => 'payment',
            'data' => ['id' => '111222333'],
        ])->assertStatus(503);

        $this->assertSame(WebhookEvent::STATUS_FAILED, WebhookEvent::query()->firstOrFail()->status);
        $this->assertSame(PaymentStatus::Pending, $this->order->refresh()->payment_status);
    }

    #[Test]
    public function it_acknowledges_but_ignores_a_topic_it_does_not_handle(): void
    {
        Http::fake();

        $this->withHeaders([
            'x-signature' => $this->signature('999'),
            'x-request-id' => self::REQUEST_ID,
        ])->postJson(route('webhooks.mercadopago').'?data.id=999', [
            'type' => 'plan',
            'data' => ['id' => '999'],
        ])->assertOk();

        $this->assertSame(WebhookEvent::STATUS_IGNORED, WebhookEvent::query()->firstOrFail()->status);
    }

    #[Test]
    public function it_ignores_a_payment_that_belongs_to_no_known_order(): void
    {
        $this->notify(payment: $this->fakePayment([
            'external_reference' => 'BO-000000-UNKNOWN',
            'metadata' => [],
        ]))->assertOk();

        $this->assertSame(0, Payment::query()->count());
        $this->assertSame(PaymentStatus::Pending, $this->order->refresh()->payment_status);
    }
}
