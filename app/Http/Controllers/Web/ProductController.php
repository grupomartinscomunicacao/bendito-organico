<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->string('q')->trim()->limit(80, '')->toString();

        $products = Product::query()
            ->active()
            ->search($search)
            ->when(
                $request->string('ordenar')->toString() === 'preco',
                fn ($query) => $query->orderBy('price'),
                fn ($query) => $query->orderByDesc('is_featured')->ordered(),
            )
            ->paginate((int) config('bendito.catalog.per_page', 12))
            ->withQueryString();

        return view('products.index', [
            'products' => $products,
            'search' => $search,
        ]);
    }

    /**
     * Route model binding resolves the slug, so an inactive product 404s
     * rather than quietly rendering a page nobody can buy from.
     */
    public function show(Product $product): View
    {
        if (! $product->is_active) {
            throw new NotFoundHttpException;
        }

        $related = Product::query()
            ->active()
            ->inStock()
            ->whereKeyNot($product->getKey())
            ->inRandomOrder()
            ->limit(4)
            ->get();

        return view('products.show', [
            'product' => $product,
            'related' => $related,
        ]);
    }
}
