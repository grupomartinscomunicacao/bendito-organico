@extends('layouts.admin')

@section('title', 'Usuários')

@section('content')

    <x-admin.page-head title="Usuários" subtitle="Quem tem acesso ao painel administrativo.">
        <x-slot:actions>
            <x-button href="{{ route('admin.users.create') }}" variant="primary" icon="person-plus">
                Novo usuário
            </x-button>
        </x-slot:actions>
    </x-admin.page-head>

    <x-admin.card flush>
        <div class="table-scroll">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Situação</th>
                        <th class="d-none d-lg-table-cell">Último acesso</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>
                                <span class="fw-semibold">{{ $user->name }}</span>
                                @if ($user->is(auth()->user()))
                                    <span class="badge-soft badge-soft--info ms-1">você</span>
                                @endif
                            </td>

                            <td class="text-muted">{{ $user->email }}</td>

                            <td><x-status-badge :status="$user->role" soft :show-icon="false" /></td>

                            <td>
                                <span class="badge-soft badge-soft--{{ $user->is_active ? 'success' : 'secondary' }}">
                                    {{ $user->is_active ? 'Ativo' : 'Desativado' }}
                                </span>
                            </td>

                            <td class="d-none d-lg-table-cell text-muted text-nowrap">
                                {{ $user->last_login_at?->format('d/m/Y H:i') ?? 'Nunca' }}
                            </td>

                            <td class="text-end text-nowrap">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline-primary" title="Editar">
                                        <i class="bi bi-pencil" aria-hidden="true"></i>
                                    </a>

                                    @can('delete', $user)
                                        <form
                                            method="POST"
                                            action="{{ route('admin.users.destroy', $user) }}"
                                            class="d-inline"
                                            data-confirm="Remover o acesso de {{ $user->name }}?"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger" title="Remover">
                                                <i class="bi bi-trash3" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <x-slot:footer>
                {{ $users->links() }}
            </x-slot:footer>
        @endif
    </x-admin.card>

@endsection
