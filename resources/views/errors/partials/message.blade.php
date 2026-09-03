<section class="section text-center">
    <div class="container" style="max-width: 40rem">
        <x-logo variant="full" size="lg" class="brand-logo mx-auto mb-4" />

        <p class="display-brand text-muted mb-2" style="font-size:clamp(3rem,10vw,5rem);line-height:1">
            {{ $code }}
        </p>

        <h1 class="mb-3">{{ $heading }}</h1>
        <p class="text-muted mb-4">{{ $message }}</p>

        <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
            <x-button href="{{ route('home') }}" variant="primary" size="lg" icon="house">
                Voltar ao início
            </x-button>
            <x-button href="{{ route('products.index') }}" variant="outline-primary" size="lg" icon="basket2">
                Ver produtos
            </x-button>
        </div>
    </div>
</section>
