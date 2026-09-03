<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Products\StoreProductImage;
use App\Enums\ProductUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProductController extends Controller
{
    public function __construct(private readonly StoreProductImage $storeImage) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Product::class);

        $status = $request->string('status')->toString();

        $products = Product::query()
            ->when($status === 'trashed', fn ($query) => $query->onlyTrashed())
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when(
                $status === 'out_of_stock',
                fn ($query) => $query->where('track_stock', true)->where('stock', '<=', 0)
            )
            ->search($request->string('q')->toString())
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'search' => $request->string('q')->toString(),
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Product::class);

        return view('admin.products.create', [
            'product' => new Product([
                'unit' => ProductUnit::Unit,
                'is_active' => true,
                'track_stock' => true,
                'stock' => 0,
            ]),
            'units' => ProductUnit::options(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $product = new Product($request->safe()->except('image'));

        // Save first so the generated slug can seed the image filename.
        $product->save();

        if ($request->hasFile('image')) {
            $product->forceFill([
                'image' => $this->storeImage->handle($product, $request->file('image')),
            ])->save();
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Produto \"{$product->name}\" cadastrado.");
    }

    public function edit(Product $product): View
    {
        Gate::authorize('update', $product);

        return view('admin.products.edit', [
            'product' => $product,
            'units' => ProductUnit::options(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->fill($request->safe()->except('image'));

        if ($request->hasFile('image')) {
            $product->image = $this->storeImage->handle($product, $request->file('image'));
        }

        $product->save();

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Produto \"{$product->name}\" atualizado.");
    }

    /**
     * Soft delete: the product leaves the storefront but the orders that
     * reference it keep working.
     */
    public function destroy(Product $product): RedirectResponse
    {
        Gate::authorize('delete', $product);

        $product->delete();

        return redirect()
            ->route('admin.products.index')
            ->with('success', "Produto \"{$product->name}\" removido. Você pode restaurá-lo na aba Removidos.");
    }

    public function restore(int $productId): RedirectResponse
    {
        $product = Product::onlyTrashed()->findOrFail($productId);

        Gate::authorize('restore', $product);

        $product->restore();

        return redirect()
            ->route('admin.products.index', ['status' => 'trashed'])
            ->with('success', "Produto \"{$product->name}\" restaurado.");
    }

    /**
     * Quick availability switch straight from the listing.
     */
    public function toggle(Product $product): RedirectResponse
    {
        Gate::authorize('update', $product);

        $product->forceFill(['is_active' => ! $product->is_active])->save();

        return back()->with(
            'success',
            $product->is_active
                ? "\"{$product->name}\" está visível na loja."
                : "\"{$product->name}\" foi ocultado da loja."
        );
    }
}
