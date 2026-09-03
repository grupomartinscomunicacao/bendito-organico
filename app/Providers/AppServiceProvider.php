<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use App\Services\CheckoutSession;
use App\Services\MercadoPagoService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            MercadoPagoService::class,
            fn () => MercadoPagoService::make(),
        );

        $this->app->scoped(
            CheckoutSession::class,
            fn ($app) => new CheckoutSession($app->make(Session::class)),
        );
    }

    public function boot(): void
    {
        $this->configureModels();
        $this->configureUrls();
        $this->configureAuth();
        $this->configureCacheInvalidation();

        Vite::prefetch(concurrency: 3);
    }

    private function configureModels(): void
    {
        // Outside production: fail loudly on a mistyped attribute and refuse
        // lazy loading, so an N+1 surfaces here rather than under real traffic.
        // Mass assignment stays guarded by each model's $fillable either way.
        Model::shouldBeStrict(! app()->isProduction());
    }

    private function configureUrls(): void
    {
        // Mercado Pago will not accept an http:// callback in production.
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }
    }

    private function configureAuth(): void
    {
        // The panel lives under /admin, so the reset link has to point there
        // rather than at Laravel's conventional /reset-password route.
        $resetUrl = static fn (object $notifiable, string $token): string => route('admin.password.reset', [
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        ResetPassword::createUrlUsing($resetUrl);

        ResetPassword::toMailUsing(
            fn (object $notifiable, string $token) => (new MailMessage)
                ->subject('Redefinição de senha — '.config('bendito.name'))
                ->greeting('Olá!')
                ->line('Recebemos um pedido para redefinir a senha do seu acesso ao painel.')
                ->action('Redefinir senha', $resetUrl($notifiable, $token))
                ->line('Este link expira em '.config('auth.passwords.users.expire', 60).' minutos.')
                ->line('Se não foi você, ignore este e-mail — nada será alterado.')
        );
    }

    /**
     * The storefront caches its shop window, so any catalog write clears it.
     */
    private function configureCacheInvalidation(): void
    {
        $flush = static function (): void {
            Cache::forget('home.showcase');
        };

        Product::saved($flush);
        Product::deleted($flush);
        Product::restored($flush);
    }
}
