@props(['product'])

@php
    $available = $product->isAvailable();
@endphp

<article @class(['product-card', 'product-card--unavailable' => ! $available])>
    <div class="product-card__media">
        <img
            src="{{ $product->image_url }}"
            alt="{{ $product->name }}"
            class="product-card__image"
            loading="lazy"
            decoding="async"
            width="800"
            height="600"
        >

        <div class="product-card__flags">
            @if ($product->has_discount)
                <span class="badge text-bg-accent">-{{ $product->discount_percentage }}%</span>
            @endif

            @if ($product->is_featured && $available)
                <span class="badge text-bg-highlight">Destaque</span>
            @endif

            @unless ($available)
                <span class="badge text-bg-danger">{{ $product->availability_label }}</span>
            @endunless
        </div>
    </div>

    <div class="product-card__body">
        <h3 class="product-card__title">
            <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
        </h3>

        @if ($product->short_description)
            <p class="product-card__description">{{ $product->short_description }}</p>
        @endif

        <div class="product-card__footer">
            <x-price
                :value="$product->price"
                :unit="$product->unit"
                :compare-at="$product->has_discount ? $product->compare_at_price : null"
            />

            @if ($available)
                <form method="POST" action="{{ route('checkout.start') }}" class="product-card__cta">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <input type="hidden" name="quantity" value="1">

                    <button type="submit" class="btn btn-primary btn-sm d-inline-flex align-items-center gap-1">
                        <i class="bi bi-basket2" aria-hidden="true"></i>
                        <span>Comprar</span>
                    </button>
                </form>
            @else
                <span class="badge-soft badge-soft--secondary">Indisponível</span>
            @endif
        </div>
    </div>
</article>
