<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Orders\SyncPaymentFromGateway;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * Where Mercado Pago sends the customer after checkout.
 *
 * The query string here is attacker-controlled — anyone can open this URL with
 * `status=approved` typed by hand. It is therefore used for one thing only:
 * working out *which* order and payment to go and ask the API about. The
 * answer that comes back from that server-side call is what decides anything.
 */
class PaymentReturnController extends Controller
{
    public function __invoke(
        Request $request,
        MercadoPagoService $gateway,
        SyncPaymentFromGateway $syncPayment,
    ): RedirectResponse {
        $reference = $request->string('external_reference')->trim()->limit(32, '')->toString();

        $order = $reference !== ''
            ? Order::query()->where('public_number', $reference)->first()
            : null;

        if ($order === null) {
            return redirect()
                ->route('home')
                ->with('info', 'Não localizamos esse pedido. Verifique o link enviado por e-mail.');
        }

        // payment_id / collection_id are hints, not evidence. Confirm with the
        // gateway before letting any of it touch the order.
        $paymentId = $request->string('payment_id')->trim()->toString()
            ?: $request->string('collection_id')->trim()->toString();

        if (ctype_digit($paymentId)) {
            $this->reconcile($gateway, $syncPayment, $order, $paymentId);
        }

        $order->refresh();

        if ($order->isPaid()) {
            return redirect()->route('orders.success', $order);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with('info', 'Recebemos seu pedido! Assim que o pagamento for confirmado pelo Mercado Pago, atualizamos esta página. Isso costuma levar alguns instantes.');
    }

    private function reconcile(
        MercadoPagoService $gateway,
        SyncPaymentFromGateway $syncPayment,
        Order $order,
        string $paymentId,
    ): void {
        try {
            $payment = $gateway->getPayment($paymentId);

            if ($payment === null) {
                return;
            }

            // Make sure the payment the URL points at really belongs to this
            // order before syncing it.
            $reference = (string) ($payment['external_reference'] ?? '');
            $metadataOrderId = (int) data_get($payment, 'metadata.order_id', 0);

            if ($reference !== $order->public_number && $metadataOrderId !== $order->id) {
                Log::warning('Payment return referenced a payment belonging to another order.', [
                    'order' => $order->public_number,
                    'payment_id' => $paymentId,
                ]);

                return;
            }

            $syncPayment->handle($payment);
        } catch (PaymentGatewayException $exception) {
            // The webhook is the reliable path; this is only a shortcut so the
            // page is already up to date when the customer lands on it.
            Log::warning('Could not reconcile the payment on return from checkout.', [
                'order' => $order->public_number,
                'payment_id' => $paymentId,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
