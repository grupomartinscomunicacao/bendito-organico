<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $limit = (int) config('bendito.catalog.featured_limit', 8);

        // The shop window changes a few times a day at most, so it is cached.
        // Product saves flush this key (see AppServiceProvider).
        $products = Cache::remember(
            'home.showcase',
            (int) config('bendito.catalog.cache_ttl', 300),
            fn () => Product::query()
                ->active()
                ->inStock()
                ->orderByDesc('is_featured')
                ->ordered()
                ->limit($limit)
                ->get()
        );

        return view('home.index', ['products' => $products]);
    }
}
