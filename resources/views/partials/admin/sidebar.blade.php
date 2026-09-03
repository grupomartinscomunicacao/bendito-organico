@php
    use App\Enums\PaymentStatus;
    use App\Models\Order;

    // Small, indexed count — cheap enough to run on every panel request.
    $pendingOrders = Order::query()->where('payment_status', PaymentStatus::Pending)->count();
@endphp

<div class="admin-sidebar__brand d-flex align-items-center justify-content-between">
    <a href="{{ route('admin.dashboard') }}" aria-label="{{ config('bendito.name') }} — painel">
        <x-logo variant="light" size="sm" class="brand-logo" />
    </a>

    <button
        type="button"
        class="btn-close btn-close-white d-lg-none"
        data-bs-dismiss="offcanvas"
        data-bs-target="#adminSidebar"
        aria-label="Fechar menu"
    ></button>
</div>

<nav class="admin-sidebar__nav" aria-label="Seções do painel">
    <p class="admin-sidebar__group">Visão geral</p>

    <a href="{{ route('admin.dashboard') }}"
       class="admin-sidebar__link @if (request()->routeIs('admin.dashboard')) active @endif">
        <i class="bi bi-speedometer2" aria-hidden="true"></i>
        Dashboard
    </a>

    <p class="admin-sidebar__group">Operação</p>

    <a href="{{ route('admin.orders.index') }}"
       class="admin-sidebar__link @if (request()->routeIs('admin.orders.*')) active @endif">
        <i class="bi bi-receipt" aria-hidden="true"></i>
        Pedidos
        @if ($pendingOrders > 0)
            <span class="admin-sidebar__badge">{{ $pendingOrders }}</span>
        @endif
    </a>

    <a href="{{ route('admin.products.index') }}"
       class="admin-sidebar__link @if (request()->routeIs('admin.products.*')) active @endif">
        <i class="bi bi-box-seam" aria-hidden="true"></i>
        Produtos
    </a>

    <a href="{{ route('admin.payments.index') }}"
       class="admin-sidebar__link @if (request()->routeIs('admin.payments.*')) active @endif">
        <i class="bi bi-credit-card-2-front" aria-hidden="true"></i>
        Pagamentos
    </a>

    @can('viewAny', App\Models\User::class)
        <p class="admin-sidebar__group">Administração</p>

        <a href="{{ route('admin.users.index') }}"
           class="admin-sidebar__link @if (request()->routeIs('admin.users.*')) active @endif">
            <i class="bi bi-people" aria-hidden="true"></i>
            Usuários
        </a>
    @endcan

    <a href="{{ route('admin.settings') }}"
       class="admin-sidebar__link @if (request()->routeIs('admin.settings')) active @endif">
        <i class="bi bi-gear" aria-hidden="true"></i>
        Configurações
    </a>
</nav>

<div class="admin-sidebar__foot">
    <div class="d-flex align-items-center gap-2">
        <x-logo variant="mark" class="brand-mark" style="height:1.75rem" />
        <div style="line-height:1.3">
            <strong class="d-block text-white" style="font-size:.8125rem">{{ config('bendito.name') }}</strong>
            <span>{{ config('bendito.tagline') }}</span>
        </div>
    </div>

    <p class="admin-sidebar__credit">
        Desenvolvido por
        <a href="{{ config('bendito.developer.url') }}" target="_blank" rel="noopener">
            {{ config('bendito.developer.name') }}
        </a>
    </p>
</div>
