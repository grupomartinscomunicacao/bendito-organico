<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Services\MercadoPagoService;
use Illuminate\Contracts\View\View;

/**
 * Read-only view of how the installation is configured.
 *
 * Deliberately shows only whether a credential is present, never its value:
 * secrets belong in .env and nowhere else.
 */
class SettingsController extends Controller
{
    public function __invoke(MercadoPagoService $gateway): View
    {
        return view('admin.settings.index', [
            'store' => config('bendito'),
            'gateway' => [
                'configured' => $gateway->isConfigured(),
                'sandbox' => $gateway->isSandbox(),
                'public_key_set' => filled(config('mercadopago.public_key')),
                'webhook_secret_set' => filled(config('mercadopago.webhook_secret')),
                'verify_signature' => (bool) config('mercadopago.verify_signature'),
                'webhook_url' => config('mercadopago.webhook_url') ?: route('webhooks.mercadopago'),
            ],
            'environment' => [
                'app_env' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'url' => config('app.url'),
                'php' => PHP_VERSION,
                'laravel' => app()->version(),
            ],
            'recentEvents' => WebhookEvent::query()->latest()->limit(10)->get(),
        ]);
    }
}
