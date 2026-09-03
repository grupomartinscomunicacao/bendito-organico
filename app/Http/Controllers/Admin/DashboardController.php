<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'counts' => $this->orderCounts(),
            'revenue' => $this->revenue(),
            'catalog' => $this->catalogCounts(),
            'recentOrders' => Order::query()
                // Eager loaded: the table renders the first item of every row.
                ->with('items')
                ->latest()
                ->limit(8)
                ->get(),
            'lowStock' => Product::query()
                ->active()
                ->where('track_stock', true)
                ->where('stock', '<=', 5)
                ->orderBy('stock')
                ->limit(5)
                ->get(),
        ]);
    }

    /**
     * One grouped query instead of six counts.
     *
     * @return array<string, int>
     */
    private function orderCounts(): array
    {
        $byStatus = Order::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $counts = ['total' => (int) $byStatus->sum()];

        foreach (OrderStatus::cases() as $status) {
            $counts[$status->value] = (int) $byStatus->get($status->value, 0);
        }

        $counts['awaiting_payment'] = Order::query()
            ->where('payment_status', PaymentStatus::Pending)
            ->whereNot('status', OrderStatus::Cancelled)
            ->count();

        $counts['paid'] = Order::query()->paid()->count();

        return $counts;
    }

    /** @return array<string, float> */
    private function revenue(): array
    {
        $paid = Order::query()->paid();

        return [
            'total' => (float) (clone $paid)->sum('total'),
            'month' => (float) (clone $paid)->whereBetween('paid_at', [
                now()->startOfMonth(),
                now()->endOfMonth(),
            ])->sum('total'),
            'today' => (float) (clone $paid)->whereDate('paid_at', today())->sum('total'),
            'average_ticket' => (float) (clone $paid)->avg('total'),
        ];
    }

    /** @return array<string, int> */
    private function catalogCounts(): array
    {
        $counts = Product::query()
            ->select(DB::raw('COUNT(*) as total'), DB::raw('SUM(is_active) as active'))
            ->first();

        return [
            'total' => (int) ($counts?->total ?? 0),
            'active' => (int) ($counts?->active ?? 0),
            'out_of_stock' => Product::query()
                ->active()
                ->where('track_stock', true)
                ->where('stock', '<=', 0)
                ->count(),
        ];
    }
}
