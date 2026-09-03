@extends('layouts.admin')

@section('title', 'Configurações')

@section('content')

    <x-admin.page-head
        title="Configurações"
        subtitle="Como esta instalação está configurada. Os valores vêm do arquivo .env."
    />

    <div class="row g-3">

        {{-- Mercado Pago --}}
        <div class="col-xl-6">
            <x-admin.card title="Mercado Pago">
                @unless ($gateway['configured'])
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i>
                        <div>
                            <strong class="d-block">Pagamentos desativados</strong>
                            Defina <code>MERCADOPAGO_ACCESS_TOKEN</code> no <code>.env</code> para
                            que os clientes consigam pagar.
                        </div>
                    </div>
                @endunless

                @if ($gateway['configured'] && $gateway['sandbox'])
                    <div class="alert alert-info" role="status">
                        <i class="bi bi-info-circle-fill" aria-hidden="true"></i>
                        <div>
                            <strong class="d-block">Modo de testes</strong>
                            As credenciais são de teste (<code>TEST-</code>). Nenhuma cobrança real é feita.
                        </div>
                    </div>
                @endif

                <div class="settings-row">
                    <span class="settings-row__label">Access token</span>
                    <span class="settings-row__value">
                        @if ($gateway['configured'])
                            <span class="badge-soft badge-soft--success">Configurado</span>
                        @else
                            <span class="badge-soft badge-soft--danger">Ausente</span>
                        @endif
                    </span>
                </div>

                <div class="settings-row">
                    <span class="settings-row__label">Public key</span>
                    <span class="settings-row__value">
                        <span class="badge-soft badge-soft--{{ $gateway['public_key_set'] ? 'success' : 'secondary' }}">
                            {{ $gateway['public_key_set'] ? 'Configurada' : 'Não definida' }}
                        </span>
                    </span>
                </div>

                <div class="settings-row">
                    <span class="settings-row__label">Segredo do webhook</span>
                    <span class="settings-row__value">
                        <span class="badge-soft badge-soft--{{ $gateway['webhook_secret_set'] ? 'success' : 'danger' }}">
                            {{ $gateway['webhook_secret_set'] ? 'Configurado' : 'Ausente' }}
                        </span>
                    </span>
                </div>

                <div class="settings-row">
                    <span class="settings-row__label">Verificação de assinatura</span>
                    <span class="settings-row__value">
                        <span class="badge-soft badge-soft--{{ $gateway['verify_signature'] ? 'success' : 'warning' }}">
                            {{ $gateway['verify_signature'] ? 'Ativa' : 'Desativada' }}
                        </span>
                    </span>
                </div>

                <div class="settings-row">
                    <span class="settings-row__label">URL do webhook</span>
                    <span class="settings-row__value"><code>{{ $gateway['webhook_url'] }}</code></span>
                </div>

                <p class="form-hint mt-3 mb-0">
                    Cadastre essa URL no painel do Mercado Pago em
                    <strong>Suas integrações &rsaquo; Webhooks</strong> e copie a assinatura secreta
                    para <code>MERCADOPAGO_WEBHOOK_SECRET</code>.
                </p>
            </x-admin.card>
        </div>

        {{-- Store --}}
        <div class="col-xl-6">
            <x-admin.card title="Loja">
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <x-logo variant="full" size="sm" class="brand-logo" />
                    <div>
                        <strong class="d-block">{{ $store['name'] }}</strong>
                        <span class="text-muted" style="font-size:.875rem">{{ $store['tagline'] }}</span>
                    </div>
                </div>

                <div class="settings-row">
                    <span class="settings-row__label">E-mail</span>
                    <span class="settings-row__value">{{ $store['contact']['email'] }}</span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Telefone</span>
                    <span class="settings-row__value">{{ $store['contact']['phone'] }}</span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Cidade</span>
                    <span class="settings-row__value">
                        {{ $store['contact']['city'] }}/{{ $store['contact']['state'] }}
                    </span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Prefixo do pedido</span>
                    <span class="settings-row__value"><code>{{ $store['orders']['number_prefix'] }}</code></span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Taxa de entrega</span>
                    <span class="settings-row__value">
                        {{ (float) $store['checkout']['delivery_fee'] > 0
                            ? \App\Support\Money::brl($store['checkout']['delivery_fee'])
                            : 'A combinar' }}
                    </span>
                </div>
            </x-admin.card>

            <x-admin.card title="Ambiente" class="mt-3">
                @if ($environment['debug'] && $environment['app_env'] === 'production')
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-octagon-fill" aria-hidden="true"></i>
                        <div>
                            <strong class="d-block">APP_DEBUG está ligado em produção</strong>
                            Isso expõe detalhes internos em telas de erro. Defina
                            <code>APP_DEBUG=false</code>.
                        </div>
                    </div>
                @endif

                <div class="settings-row">
                    <span class="settings-row__label">Ambiente</span>
                    <span class="settings-row__value"><code>{{ $environment['app_env'] }}</code></span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Debug</span>
                    <span class="settings-row__value">
                        <span class="badge-soft badge-soft--{{ $environment['debug'] ? 'warning' : 'success' }}">
                            {{ $environment['debug'] ? 'Ligado' : 'Desligado' }}
                        </span>
                    </span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">URL da aplicação</span>
                    <span class="settings-row__value"><code>{{ $environment['url'] }}</code></span>
                </div>
                <div class="settings-row">
                    <span class="settings-row__label">Laravel / PHP</span>
                    <span class="settings-row__value">{{ $environment['laravel'] }} · {{ $environment['php'] }}</span>
                </div>
            </x-admin.card>
        </div>

        {{-- Webhook log --}}
        <div class="col-12">
            <x-admin.card title="Últimas notificações recebidas" flush>
                @if ($recentEvents->isEmpty())
                    <div class="admin-card__body">
                        <x-empty-state icon="broadcast" title="Nenhuma notificação ainda">
                            Assim que o Mercado Pago enviar um evento para o webhook, ele aparece aqui.
                        </x-empty-state>
                    </div>
                @else
                    <div class="table-scroll">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Recebido</th>
                                    <th>Tópico</th>
                                    <th>Ação</th>
                                    <th>Recurso</th>
                                    <th>Resultado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($recentEvents as $event)
                                    <tr>
                                        <td class="text-muted text-nowrap">{{ $event->created_at->format('d/m/Y H:i:s') }}</td>
                                        <td>{{ $event->topic ?: '—' }}</td>
                                        <td class="text-muted">{{ $event->action ?: '—' }}</td>
                                        <td class="order-ref">{{ $event->resource_id ?: '—' }}</td>
                                        <td>
                                            @php
                                                $variant = match ($event->status) {
                                                    'processed' => 'success',
                                                    'ignored' => 'secondary',
                                                    'failed' => 'danger',
                                                    default => 'warning',
                                                };
                                            @endphp
                                            <span class="badge-soft badge-soft--{{ $variant }}">{{ $event->status }}</span>

                                            @if ($event->error)
                                                <span class="d-block text-muted text-truncate" style="max-width:22rem;font-size:.8125rem">
                                                    {{ $event->error }}
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-admin.card>
        </div>
    </div>

@endsection
