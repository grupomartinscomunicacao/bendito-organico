<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Web\CheckoutController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\OrderController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\PaymentReturnController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Storefront
|--------------------------------------------------------------------------
|
| Public, guest-only by design: buying never requires an account.
|
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/produtos', [ProductController::class, 'index'])->name('products.index');
Route::get('/produtos/{product}', [ProductController::class, 'show'])->name('products.show');

Route::get('/sobre-nos', [PageController::class, 'about'])->name('about');
Route::get('/contato', [PageController::class, 'contact'])->name('contact');
Route::post('/contato', [PageController::class, 'sendContact'])
    ->middleware('throttle:6,1')
    ->name('contact.send');

/*
|--------------------------------------------------------------------------
| Checkout
|--------------------------------------------------------------------------
|
| Throttled: creating orders and calling the payment gateway are the two
| endpoints worth abusing.
|
*/

Route::post('/checkout', [CheckoutController::class, 'start'])
    ->middleware('throttle:30,1')
    ->name('checkout.start');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout.show');

Route::post('/pedido', [CheckoutController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('orders.store');

/*
|--------------------------------------------------------------------------
| Orders
|--------------------------------------------------------------------------
|
| Bound by public_number, which carries a random suffix, so a link is shareable
| but the sequence is not walkable. Customers reach their own orders through
| /meu-pedido, which looks them up by the phone given at checkout.
|
*/

Route::get('/meu-pedido', [OrderController::class, 'lookup'])->name('orders.lookup');
Route::post('/meu-pedido', [OrderController::class, 'search'])
    ->middleware('throttle:20,1')
    ->name('orders.lookup.search');

Route::get('/pedido/{order}', [OrderController::class, 'show'])->name('orders.show');
Route::get('/pedido/{order}/sucesso', [OrderController::class, 'success'])->name('orders.success');
Route::post('/pedido/{order}/pagar', [OrderController::class, 'pay'])
    ->middleware('throttle:10,1')
    ->name('orders.pay');

Route::get('/pagamento/retorno', PaymentReturnController::class)->name('payment.return');

/*
|--------------------------------------------------------------------------
| Webhooks
|--------------------------------------------------------------------------
|
| No session, no CSRF (see bootstrap/app.php) — authenticity comes from the
| HMAC signature, and the payload is re-verified against the gateway API.
|
*/

Route::post('/webhooks/mercadopago', MercadoPagoWebhookController::class)
    ->middleware('throttle:120,1')
    ->name('webhooks.mercadopago');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/

Route::prefix('admin')->name('admin.')->group(function (): void {

    // --- Guest ------------------------------------------------------------
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('login.store');

        Route::get('/esqueci-a-senha', [PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/esqueci-a-senha', [PasswordResetLinkController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('password.email');

        Route::get('/redefinir-senha/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/redefinir-senha', [NewPasswordController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('password.update');
    });

    // --- Authenticated ----------------------------------------------------
    Route::middleware(['auth', 'panel'])->group(function (): void {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/', DashboardController::class)->name('dashboard');

        // Produtos
        Route::controller(AdminProductController::class)->group(function (): void {
            Route::get('/produtos', 'index')->name('products.index');
            Route::get('/produtos/criar', 'create')->name('products.create');
            Route::post('/produtos', 'store')->name('products.store');
            Route::get('/produtos/{product}/editar', 'edit')->name('products.edit');
            Route::put('/produtos/{product}', 'update')->name('products.update');
            Route::delete('/produtos/{product}', 'destroy')->name('products.destroy');
            Route::patch('/produtos/{product}/disponibilidade', 'toggle')->name('products.toggle');
            // Restore takes a raw id: a soft-deleted model cannot be bound.
            Route::post('/produtos/{productId}/restaurar', 'restore')
                ->whereNumber('productId')
                ->name('products.restore');
        });

        // Pedidos
        Route::controller(AdminOrderController::class)->group(function (): void {
            Route::get('/pedidos', 'index')->name('orders.index');
            Route::get('/pedidos/{order}', 'show')->name('orders.show');
            Route::patch('/pedidos/{order}/situacao', 'updateStatus')->name('orders.status');
            Route::get('/pedidos/{order}/imprimir', 'print')->name('orders.print');
        });

        // Pagamentos
        Route::controller(AdminPaymentController::class)->group(function (): void {
            Route::get('/pagamentos', 'index')->name('payments.index');
            Route::get('/pagamentos/{payment}', 'show')->name('payments.show');
            Route::post('/pagamentos/{payment}/sincronizar', 'sync')->name('payments.sync');
        });

        // Usuários
        Route::controller(AdminUserController::class)->group(function (): void {
            Route::get('/usuarios', 'index')->name('users.index');
            Route::get('/usuarios/criar', 'create')->name('users.create');
            Route::post('/usuarios', 'store')->name('users.store');
            Route::get('/usuarios/{user}/editar', 'edit')->name('users.edit');
            Route::put('/usuarios/{user}', 'update')->name('users.update');
            Route::delete('/usuarios/{user}', 'destroy')->name('users.destroy');
        });

        Route::get('/configuracoes', SettingsController::class)->name('settings');
    });
});
