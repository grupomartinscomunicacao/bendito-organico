<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\SyncPaymentFromGateway;
use App\Enums\PaymentStatus;
use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\MercadoPagoService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PaymentController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Payment::class);

        $payments = Payment::query()
            ->with('order')
            ->when(
                $request->string('status')->toString(),
                fn ($query, string $status) => $query->where('status', $status)
            )
            ->search($request->string('q')->toString())
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.payments.index', [
            'payments' => $payments,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
            ],
            'statusOptions' => PaymentStatus::options(),
        ]);
    }

    public function show(Payment $payment): View
    {
        Gate::authorize('view', $payment);

        $payment->load('order.items');

        return view('admin.payments.show', ['payment' => $payment]);
    }

    /**
     * Re-reads the transaction from Mercado Pago and reapplies it.
     *
     * The manual escape hatch for when a webhook was missed — it goes through
     * the same verified path, so it cannot invent a payment that did not
     * happen.
     */
    public function sync(Payment $payment, MercadoPagoService $gateway, SyncPaymentFromGateway $sync): RedirectResponse
    {
        Gate::authorize('sync', $payment);

        try {
            $data = $gateway->getPayment($payment->external_id);
        } catch (PaymentGatewayException $exception) {
            return back()->with('error', 'Não foi possível consultar o Mercado Pago agora. Tente novamente em instantes.');
        }

        if ($data === null) {
            return back()->with('error', 'O Mercado Pago não reconhece mais esta transação.');
        }

        $sync->handle($data);

        return back()->with('success', 'Transação reconsultada e atualizada.');
    }
}
