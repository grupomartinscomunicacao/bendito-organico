<?php

declare(strict_types=1);

use App\Http\Middleware\EnsureUserCanAccessPanel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'panel' => EnsureUserCanAccessPanel::class,
        ]);

        // The gateway cannot carry a session token. Authenticity is proven by
        // the HMAC signature instead, and the payload is re-verified against
        // the Mercado Pago API before anything is written.
        $middleware->validateCsrfTokens(except: [
            'webhooks/mercadopago',
        ]);

        // Both sides of the panel: guests land on the login screen, and a
        // signed-in operator who opens /admin/login goes to the dashboard.
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        $middleware->redirectUsersTo(fn () => route('admin.dashboard'));

        // Behind a load balancer or a tunnel, trust the forwarded headers so
        // generated URLs (and the webhook callback) keep the right scheme.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Never let a stack trace reach a customer: the storefront gets a
        // branded error page, JSON clients get a plain message.
        $exceptions->dontReportDuplicates();

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('webhooks/*') || $request->expectsJson()
        );
    })->create();
