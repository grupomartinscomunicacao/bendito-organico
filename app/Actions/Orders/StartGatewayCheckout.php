<?php

declare(strict_types=1);

namespace App\Actions\Orders;

use App\Exceptions\PaymentGatewayException;
use App\Models\Order;
use App\Services\MercadoPagoService;

/**
 * Hands an order over to Mercado Pago and returns where to send the customer.
 *
 * Reuses a preference that was already created for the order so a customer who
 * refreshes, or comes back later from the order page, keeps paying against the
 * same checkout instead of spawning a new one each time.
 */
class StartGatewayCheckout
{
    public function __construct(private readonly MercadoPagoService $gateway) {}

    /**
     * @throws PaymentGatewayException
     */
    public function handle(Order $order): string
    {
        $preference = $this->gateway->createPreference($order);

        $order->forceFill(['gateway_preference_id' => $preference['id']])->save();

        return $preference['init_point'];
    }
}
