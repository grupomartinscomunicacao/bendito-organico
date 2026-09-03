<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Orders\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);

        $orders = Order::query()
            // Without this the listing runs one query per row to name the
            // product: the classic N+1.
            ->with('items')
            ->status($request->string('status')->toString() ?: null)
            ->paymentStatus($request->string('pagamento')->toString() ?: null)
            ->search($request->string('q')->toString())
            ->placedBetween(
                $request->date('de')?->toDateString(),
                $request->date('ate')?->toDateString(),
            )
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'filters' => [
                'q' => $request->string('q')->toString(),
                'status' => $request->string('status')->toString(),
                'pagamento' => $request->string('pagamento')->toString(),
                'de' => $request->string('de')->toString(),
                'ate' => $request->string('ate')->toString(),
            ],
            'statusOptions' => OrderStatus::options(),
            'paymentOptions' => PaymentStatus::options(),
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['items.product', 'address', 'payments']);

        return view('admin.orders.show', [
            'order' => $order,
            // Only the transitions the workflow actually permits are offered.
            'transitions' => $order->status->allowedTransitions(),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order, UpdateOrderStatus $action): RedirectResponse
    {
        $action->handle(
            order: $order,
            target: $request->status(),
            internalNotes: $request->input('internal_notes'),
        );

        return redirect()
            ->route('admin.orders.show', $order)
            ->with('success', 'Situação do pedido atualizada.');
    }

    /**
     * Standalone print view: a delivery card rendered with the print layout,
     * no chrome, no sidebar.
     */
    public function print(Order $order): View
    {
        Gate::authorize('print', $order);

        $order->load(['items', 'address']);

        return view('admin.orders.print', ['order' => $order]);
    }
}
