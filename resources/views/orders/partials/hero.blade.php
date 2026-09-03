{{--
    Cabeçalho das páginas de pedido.

    O número do pedido não aparece de propósito: o cliente chega aqui pelo
    telefone ou pelo link que recebeu, então o código só seria mais uma
    informação para ignorar.
--}}
<section class="order-hero">
    <div class="container">
        <x-logo variant="light" size="lg" class="brand-logo mx-auto mb-4" />

        <span class="order-hero__icon">
            <i class="bi bi-{{ $icon }}" aria-hidden="true"></i>
        </span>

        <h1 class="display-brand mb-2">{{ $heading }}</h1>
        <p class="mb-0 mx-auto" style="max-width: 46ch">{{ $lead }}</p>

        <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
            <x-status-badge :status="$order->payment_status" />
            <x-status-badge :status="$order->status" />
        </div>
    </div>
</section>
