<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Orders\CreateOrder;
use App\Actions\Orders\StartGatewayCheckout;
use App\Exceptions\CheckoutException;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreCheckoutRequest;
use App\Http\Requests\Web\StoreOrderRequest;
use App\Services\CheckoutSession;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutSession $basket) {}

    /**
     * "Fazer pedido" on a product page. Records the intent and moves the
     * visitor to the form; no order exists yet.
     */
    public function start(StoreCheckoutRequest $request): RedirectResponse
    {
        $product = $request->product();
        $quantity = $request->quantity();

        if (! $product->hasStock($quantity)) {
            return back()
                ->withInput()
                ->with('error', CheckoutException::insufficientStock($product)->getMessage());
        }

        // Barrado já aqui, na página do produto, onde o cliente ainda está com
        // o seletor de quantidade na frente. CreateOrder confere de novo — esta
        // checagem é de conveniência, não é a que garante a regra.
        if (! $product->meetsMinimumOrder($quantity)) {
            return back()
                ->withInput()
                ->with('error', CheckoutException::belowMinimum($product->subtotalFor($quantity))->getMessage());
        }

        $this->basket->put($product, $quantity);

        return redirect()->route('checkout.show');
    }

    /**
     * The checkout form. Totals shown here are recomputed from the database
     * on every render, so a price change is reflected immediately.
     */
    public function show(): View|RedirectResponse
    {
        $basket = $this->basket->resolve();

        if ($basket === null) {
            return redirect()
                ->route('products.index')
                ->with('error', CheckoutException::emptyBasket()->getMessage());
        }

        $product = $basket['product'];
        $subtotal = $product->subtotalFor($basket['quantity']);
        $deliveryFee = round((float) config('bendito.checkout.delivery_fee', 0), 2);

        // A cesta é resolvida de novo a cada render, e a quantidade é aparada
        // pelo estoque disponível. Uma queda de estoque ou uma mudança de preço
        // pode ter derrubado o pedido abaixo do mínimo depois que ele foi
        // montado — melhor devolver o cliente ao produto do que deixá-lo
        // preencher o formulário inteiro para falhar no envio.
        if (! $product->meetsMinimumOrder($basket['quantity'])) {
            return redirect()
                ->route('products.show', $product)
                ->with('error', CheckoutException::belowMinimum($subtotal)->getMessage());
        }

        return view('orders.checkout', [
            'product' => $product,
            'quantity' => $basket['quantity'],
            'subtotal' => $subtotal,
            'deliveryFee' => $deliveryFee,
            'total' => round($subtotal + $deliveryFee, 2),
        ]);
    }

    /**
     * Creates the order and hands the customer to Mercado Pago.
     *
     * If the gateway is unreachable the order is still saved — losing a paid
     * customer to a transient API error would be worse than showing them a
     * "pay again" button on their order page.
     */
    public function store(
        StoreOrderRequest $request,
        CreateOrder $createOrder,
        StartGatewayCheckout $startCheckout,
    ): RedirectResponse {
        $basket = $this->basket->resolve();

        if ($basket === null) {
            return redirect()
                ->route('products.index')
                ->with('error', CheckoutException::emptyBasket()->getMessage());
        }

        try {
            $order = $createOrder->handle(
                product: $basket['product'],
                quantity: $basket['quantity'],
                data: $request->orderData(),
            );
        } catch (CheckoutException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        $this->basket->forget();

        try {
            return redirect()->away($startCheckout->handle($order));
        } catch (PaymentGatewayException $exception) {
            Log::error('Could not start the Mercado Pago checkout.', [
                'order' => $order->public_number,
                'message' => $exception->getMessage(),
            ] + $exception->context());

            return redirect()
                ->route('orders.show', $order)
                ->with('warning', 'Seu pedido foi registrado, mas não conseguimos abrir o pagamento agora. Use o botão abaixo para tentar novamente.');
        }
    }
}
