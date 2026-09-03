<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProductUnit;
use App\Services\MercadoPagoService;
use App\Support\Money;
use App\Support\Phone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Pure domain rules — no database, no HTTP.
 */
class DomainRulesTest extends TestCase
{
    /*
    |--------------------------------------------------------------------------
    | Order workflow
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function the_workflow_allows_only_forward_steps_and_cancellation(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed));
        $this->assertTrue(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Preparing));
        $this->assertTrue(OrderStatus::Preparing->canTransitionTo(OrderStatus::Shipped));
        $this->assertTrue(OrderStatus::Shipped->canTransitionTo(OrderStatus::Delivered));

        // No skipping ahead.
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Delivered));
        $this->assertFalse(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Shipped));

        // No going back.
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Shipped));
        $this->assertFalse(OrderStatus::Shipped->canTransitionTo(OrderStatus::Preparing));
    }

    #[Test]
    public function delivered_and_cancelled_are_terminal(): void
    {
        $this->assertTrue(OrderStatus::Delivered->isFinal());
        $this->assertTrue(OrderStatus::Cancelled->isFinal());
        $this->assertFalse(OrderStatus::Pending->isFinal());
    }

    #[Test]
    public function every_order_status_has_a_label_and_a_badge_variant(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertNotSame('', $status->label());
            $this->assertNotSame('', $status->variant());
            $this->assertNotSame('', $status->icon());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Gateway status mapping
    |--------------------------------------------------------------------------
    */

    #[Test]
    #[DataProvider('gatewayStatuses')]
    public function it_maps_gateway_statuses_conservatively(string $gateway, PaymentStatus $expected): void
    {
        $this->assertSame($expected, PaymentStatus::fromMercadoPago($gateway));
    }

    /** @return array<string, array{string, PaymentStatus}> */
    public static function gatewayStatuses(): array
    {
        return [
            'approved' => ['approved', PaymentStatus::Approved],
            'rejected' => ['rejected', PaymentStatus::Rejected],
            'cancelled' => ['cancelled', PaymentStatus::Cancelled],
            'expired' => ['expired', PaymentStatus::Cancelled],
            'refunded' => ['refunded', PaymentStatus::Refunded],
            'charged_back' => ['charged_back', PaymentStatus::Refunded],
            'pending' => ['pending', PaymentStatus::Pending],
            // Anything not recognised must never be read as "money arrived".
            'in_process' => ['in_process', PaymentStatus::Pending],
            'in_mediation' => ['in_mediation', PaymentStatus::Pending],
            'authorized' => ['authorized', PaymentStatus::Pending],
            'nonsense' => ['something_new_from_the_api', PaymentStatus::Pending],
        ];
    }

    #[Test]
    public function an_unknown_status_never_maps_to_approved(): void
    {
        $this->assertNotSame(PaymentStatus::Approved, PaymentStatus::fromMercadoPago(null));
        $this->assertNotSame(PaymentStatus::Approved, PaymentStatus::fromMercadoPago(''));
    }

    /*
    |--------------------------------------------------------------------------
    | Webhook signature
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_accepts_a_correctly_signed_manifest(): void
    {
        $secret = 'super-secret';
        $service = new MercadoPagoService('TEST-token', $secret);

        $ts = time();
        $manifest = "id:12345;request-id:req-1;ts:{$ts};";
        $hash = hash_hmac('sha256', $manifest, $secret);

        $this->assertTrue($service->verifySignature("ts={$ts},v1={$hash}", 'req-1', '12345'));
    }

    #[Test]
    public function it_rejects_a_manifest_signed_with_the_wrong_secret(): void
    {
        $service = new MercadoPagoService('TEST-token', 'right-secret');

        $ts = time();
        $hash = hash_hmac('sha256', "id:12345;request-id:req-1;ts:{$ts};", 'wrong-secret');

        $this->assertFalse($service->verifySignature("ts={$ts},v1={$hash}", 'req-1', '12345'));
    }

    #[Test]
    public function it_rejects_a_signature_when_no_secret_is_configured(): void
    {
        $service = new MercadoPagoService('TEST-token', null);

        $ts = time();
        $hash = hash_hmac('sha256', "id:12345;request-id:req-1;ts:{$ts};", '');

        $this->assertFalse($service->verifySignature("ts={$ts},v1={$hash}", 'req-1', '12345'));
    }

    #[Test]
    public function it_rejects_a_malformed_signature_header(): void
    {
        $service = new MercadoPagoService('TEST-token', 'secret');

        $this->assertFalse($service->verifySignature(null, 'req-1', '12345'));
        $this->assertFalse($service->verifySignature('', 'req-1', '12345'));
        $this->assertFalse($service->verifySignature('garbage', 'req-1', '12345'));
        $this->assertFalse($service->verifySignature('ts=123', 'req-1', '12345'));
    }

    #[Test]
    public function test_credentials_put_the_service_in_sandbox_mode(): void
    {
        $this->assertTrue((new MercadoPagoService('TEST-abc', 's'))->isSandbox());
        $this->assertFalse((new MercadoPagoService('APP_USR-abc', 's'))->isSandbox());
    }

    #[Test]
    public function a_service_without_a_token_reports_itself_unconfigured(): void
    {
        $this->assertFalse((new MercadoPagoService(null, null))->isConfigured());
        $this->assertTrue((new MercadoPagoService('TEST-abc', null))->isConfigured());
    }

    /*
    |--------------------------------------------------------------------------
    | Formatting
    |--------------------------------------------------------------------------
    */

    #[Test]
    public function it_formats_money_the_brazilian_way(): void
    {
        $this->assertSame('R$ 5,90', Money::brl(5.9));
        $this->assertSame('R$ 1.234,50', Money::brl(1234.5));
        $this->assertSame('R$ 0,00', Money::brl(null));
    }

    #[Test]
    public function it_drops_meaningless_trailing_zeros_from_quantities(): void
    {
        $this->assertSame('2', Money::quantity(2.000));
        $this->assertSame('1,5', Money::quantity(1.5));
        $this->assertSame('0,25', Money::quantity(0.25));
    }

    #[Test]
    public function produce_sold_by_weight_can_be_bought_in_half_units(): void
    {
        $this->assertSame(0.5, ProductUnit::Kilogram->step());
        $this->assertSame(1.0, ProductUnit::Unit->step());
        $this->assertSame(1.0, ProductUnit::Bunch->step());
    }

    /*
    |--------------------------------------------------------------------------
    | Phone numbers
    |--------------------------------------------------------------------------
    |
    | Whatever shape the customer types, "meu pedido" has to recognise it as
    | the same number the checkout stored.
    */

    #[Test]
    #[DataProvider('phoneVariants')]
    public function every_way_of_writing_a_number_normalises_to_the_same_digits(string $typed): void
    {
        $this->assertSame('77999999999', Phone::normalize($typed));
    }

    /** @return array<string, array{string}> */
    public static function phoneVariants(): array
    {
        return [
            'masked' => ['(77) 99999-9999'],
            'spaced' => ['77 99999-9999'],
            'bare' => ['77999999999'],
            'country code' => ['+55 77 99999-9999'],
            'country code, no mask' => ['5577999999999'],
            'noisy' => [' 77 . 99999 / 9999 '],
        ];
    }

    /**
     * "55" is also a valid São Paulo landline prefix, so it is only treated
     * as a country code when what remains is still a plausible number.
     */
    #[Test]
    public function a_landline_starting_with_55_keeps_all_of_its_digits(): void
    {
        $this->assertSame('5512345678', Phone::normalize('(55) 1234-5678'));
    }

    #[Test]
    public function it_formats_and_expands_numbers_for_display_and_whatsapp(): void
    {
        $this->assertSame('(11) 98888-7777', Phone::format('11988887777'));
        $this->assertSame('(11) 3456-7890', Phone::format('1134567890'));
        $this->assertSame('5511988887777', Phone::withCountryCode('(11) 98888-7777'));

        // Nothing to work with: hand back what came in rather than inventing.
        $this->assertSame('sem telefone', Phone::format('sem telefone'));
        $this->assertSame('', Phone::withCountryCode(null));
        $this->assertSame([], Phone::variants(''));
    }
}
