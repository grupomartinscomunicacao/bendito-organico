@php
    /** @var \App\Models\User $user */
    $isEdit = $user->exists;
    $isSelf = $isEdit && $user->is(auth()->user());
@endphp

<div class="row g-3">
    <div class="col-lg-7">
        <x-admin.card title="Dados de acesso">
            <x-form.input
                name="name"
                label="Nome"
                :value="$user->name"
                maxlength="120"
                required
                autofocus
            />

            <x-form.input
                name="email"
                type="email"
                label="E-mail"
                :value="$user->email"
                maxlength="180"
                required
            />

            <div class="row">
                <div class="col-md-6">
                    <x-form.input
                        name="password"
                        type="password"
                        label="{{ $isEdit ? 'Nova senha' : 'Senha' }}"
                        autocomplete="new-password"
                        :required="! $isEdit"
                        hint="{{ $isEdit ? 'Deixe em branco para manter a senha atual.' : 'Mínimo de 8 caracteres.' }}"
                    />
                </div>
                <div class="col-md-6">
                    <x-form.input
                        name="password_confirmation"
                        type="password"
                        label="Confirmar senha"
                        autocomplete="new-password"
                        :required="! $isEdit"
                    />
                </div>
            </div>
        </x-admin.card>
    </div>

    <div class="col-lg-5">
        <x-admin.card title="Permissões">
            @if ($isSelf)
                <div class="alert alert-info" role="status">
                    <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                    <div>
                        Você não pode alterar o próprio perfil nem desativar a própria conta —
                        é o que impede alguém de se trancar para fora do painel.
                    </div>
                </div>
            @endif

            {{-- A disabled control posts nothing, so when editing yourself the
                 current role rides along in a hidden field and the server
                 discards any change anyway. --}}
            @if ($isSelf)
                <input type="hidden" name="role" value="{{ $user->role?->value }}">
            @endif

            <x-form.select
                name="{{ $isSelf ? 'role_display' : 'role' }}"
                label="Perfil"
                :options="$roles"
                :value="$user->role?->value"
                :disabled="$isSelf"
                :required="! $isSelf"
            />

            <p class="form-hint mb-3">
                <strong>Administrador</strong> gerencia usuários e configurações.
                <strong>Operador</strong> cuida de produtos e pedidos.
            </p>

            <x-form.checkbox
                name="{{ $isSelf ? 'is_active_display' : 'is_active' }}"
                label="Conta ativa"
                :checked="$user->is_active ?? true"
                :disabled="$isSelf"
                hint="Contas desativadas não conseguem entrar no painel."
                switch
                class="mb-0"
            />
        </x-admin.card>

        <div class="d-grid gap-2 mt-3">
            <x-button variant="primary" size="lg" icon="check2">
                {{ $isEdit ? 'Salvar alterações' : 'Criar usuário' }}
            </x-button>

            <x-button href="{{ route('admin.users.index') }}" variant="outline-secondary">
                Cancelar
            </x-button>
        </div>
    </div>
</div>
