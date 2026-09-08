@extends('layouts.admin')

@section('title', 'Produtos')

@section('content')

    <x-admin.page-head title="Produtos" subtitle="Gerencie o catálogo da loja.">
        <x-slot:actions>
            <x-button href="{{ route('admin.products.create') }}" variant="primary" icon="plus-lg">
                Novo produto
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <x-admin.card flush>
        <div class="admin-card__body border-bottom">
            <form method="GET" action="{{ route('admin.products.index') }}" class="filter-bar">
                <div style="grid-column: span 2">
                    <label class="form-label" for="filtro-busca">Buscar</label>
                    <input
                        type="search"
                        id="filtro-busca"
                        name="q"
                        value="{{ $search }}"
                        class="form-control"
                        placeholder="Nome ou slug…"
                    >
                </div>

                <div>
                    <label class="form-label" for="filtro-status">Situação</label>
                    <select name="status" id="filtro-status" class="form-select">
                        <option value="">Todos</option>
                        <option value="active" @selected($status === 'active')>Ativos</option>
                        <option value="inactive" @selected($status === 'inactive')>Inativos</option>
                        <option value="out_of_stock" @selected($status === 'out_of_stock')>Esgotados</option>
                        <option value="trashed" @selected($status === 'trashed')>Removidos</option>
                    </select>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Filtrar</button>
                    @if ($search !== '' || $status !== '')
                        <a href="{{ route('admin.products.index') }}" class="btn btn-outline-secondary">Limpar</a>
                    @endif
                </div>
            </form>
        </div>

        @if ($products->isEmpty())
            <div class="admin-card__body">
                <x-empty-state icon="box-seam" title="Nenhum produto encontrado">
                    Ajuste os filtros ou cadastre o primeiro item do catálogo.

                    <x-slot:action>
                        <x-button href="{{ route('admin.products.create') }}" variant="primary" icon="plus-lg">
                            Cadastrar produto
                        </x-button>
                    </x-slot:action>
                </x-empty-state>
            </div>
        @else
            <div class="table-scroll">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th style="width:3.5rem"></th>
                            <th>Produto</th>
                            <th>Preço</th>
                            <th class="d-none d-md-table-cell">Estoque</th>
                            <th>Situação</th>
                            <th class="text-end">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($products as $product)
                            <tr>
                                <td>
                                    <img src="{{ $product->image_url }}" alt="" class="table-thumb" width="44" height="44" loading="lazy">
                                </td>

                                <td>
                                    <span class="d-block fw-semibold text-truncate" style="max-width:18rem">{{ $product->name }}</span>
                                    <span class="text-muted" style="font-size:.8125rem">/{{ $product->slug }}</span>
                                </td>

                                <td class="text-nowrap">
                                    <span class="fw-semibold">{{ \App\Support\Money::brl($product->price) }}</span>
                                    <span class="text-muted d-block" style="font-size:.8125rem">por {{ $product->unit->abbreviation() }}</span>
                                </td>

                                <td class="d-none d-md-table-cell text-nowrap">
                                    @if ($product->track_stock)
                                        <span @class(['fw-semibold', 'text-danger' => (float) $product->stock <= 0])>
                                            {{ \App\Support\Money::quantity($product->stock) }}
                                        </span>
                                        <span class="text-muted">{{ $product->unit->abbreviationFor((float) $product->stock) }}</span>
                                    @else
                                        <span class="text-muted">Ilimitado</span>
                                    @endif
                                </td>

                                <td>
                                    @if ($product->trashed())
                                        <span class="badge-soft badge-soft--secondary">Removido</span>
                                    @else
                                        <span class="badge-soft badge-soft--{{ $product->availability_variant }}">
                                            {{ $product->availability_label }}
                                        </span>
                                    @endif
                                </td>

                                <td class="text-end text-nowrap">
                                    @if ($product->trashed())
                                        <form method="POST" action="{{ route('admin.products.restore', $product->id) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Restaurar">
                                                <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>
                                                <span class="d-none d-lg-inline ms-1">Restaurar</span>
                                            </button>
                                        </form>
                                    @else
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary" target="_blank" rel="noopener" title="Ver na loja">
                                                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i>
                                            </a>

                                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline-primary" title="Editar">
                                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                            </a>

                                            <form method="POST" action="{{ route('admin.products.toggle', $product) }}" class="d-inline">
                                                @csrf
                                                @method('PATCH')
                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-secondary"
                                                    title="{{ $product->is_active ? 'Ocultar da loja' : 'Publicar na loja' }}"
                                                >
                                                    <i class="bi bi-{{ $product->is_active ? 'eye-slash' : 'eye' }}" aria-hidden="true"></i>
                                                </button>
                                            </form>

                                            <form
                                                method="POST"
                                                action="{{ route('admin.products.destroy', $product) }}"
                                                class="d-inline"
                                                data-confirm="Remover &quot;{{ $product->name }}&quot; do catálogo? Você poderá restaurá-lo depois."
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger" title="Remover">
                                                    <i class="bi bi-trash3" aria-hidden="true"></i>
                                                </button>
                                            </form>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($products->hasPages())
                <x-slot:footer>
                    {{ $products->links() }}
                </x-slot:footer>
            @endif
        @endif
    </x-admin.card>

@endsection
