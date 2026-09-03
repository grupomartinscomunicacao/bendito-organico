<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Orders\StartGatewayCheckout;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\LookupOrdersRequest;
use App\Models\Order;
use App\Support\Phone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * The customer-facing view of an order.
 *
 * Orders are bound by their public number, which carries a random suffix, so
 * the page is reachable by anyone holding the link but not by anyone guessing
 * sequential ids. The customer never has to know that number: "meu pedido"
 * finds their orders by the phone they gave at checkout.
 */
class OrderController extends Controller
{
    /**
     * Where the looked-up phone lives between requests.
     *
     * Keeping it in the session rather than the query string means the number
     * never lands in browser history, a shared link or an access log, and the
     * customer can come back from an order to their list without retyping it.
     */
    private const PHONE_SESSION_KEY = 'orders.lookup.phone';

    /**
     * "Meu pedido": the phone form, or the orders found for the phone already
     * entered in this session.
     */
    public function lookup(Request $request): View|RedirectResponse
    {
        // "Buscar outro telefone" — drop the number and start over on a clean
        // URL, so a refresh does not re-trigger the reset.
        if ($request->boolean('novo')) {
            $request->session()->forget(self::PHONE_SESSION_KEY);

            return redirect()->route('orders.lookup');
        }

        $phone = (string) $request->session()->get(self::PHONE_SESSION_KEY, '');

        if ($phone === '') {
            return view('orders.lookup', ['orders' => null, 'phone' => null]);
        }

        $orders = Order::query()
            ->forPhone($phone)
            ->with('items')
            ->latest()
            ->limit(30)
            ->get();

        return view('orders.lookup', [
            'orders' => $orders,
            'phone' => Phone::format($phone),
        ]);
    }

    /**
     * Accepts the phone and hands off to the list.
     *
     * Post/redirect/get on purpose: the result page is refreshable and the
     * number stays out of the URL.
     */
    public function search(LookupOrdersRequest $request): RedirectResponse
    {
        $request->session()->put(self::PHONE_SESSION_KEY, $request->phone());

        return redirect()->route('orders.lookup');
    }

    public function show(Order $order): View
    {
        $order->load(['items', 'address', 'latestPayment']);

        return view('orders.show', ['order' => $order]);
    }

    /**
     * Landing page after a successful checkout. It reports what the database
     * says — which is only ever written from a verified gateway response.
     */
    public function success(Order $order): View
    {
        $order->load(['items', 'address', 'latestPayment']);

        return view('orders.success', ['order' => $order]);
    }

    /**
     * Sends a customer back to the gateway for an order that is still unpaid.
     */
    public function pay(Order $order, StartGatewayCheckout $startCheckout): RedirectResponse
    {
        if (! $order->isPayable()) {
            return redirect()
                ->route('orders.show', $order)
                ->with('info', 'Este pedido não está aguardando pagamento.');
        }

        try {
            return redirect()->away($startCheckout->handle($order));
        } catch (PaymentGatewayException $exception) {
            Log::error('Could not reopen the Mercado Pago checkout.', [
                'order' => $order->public_number,
                'message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('orders.show', $order)
                ->with('error', 'Não conseguimos abrir o pagamento agora. Tente novamente em alguns instantes.');
        }
    }
}
