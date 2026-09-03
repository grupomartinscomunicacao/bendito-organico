<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Second gate behind `auth`: a staff account that has been deactivated is
 * signed out on its next request rather than keeping a live session until it
 * happens to expire.
 */
class EnsureUserCanAccessPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || ! $user->canAccessPanel()) {
            Auth::guard('web')->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => 'Sua conta foi desativada. Fale com um administrador.']);
        }

        return $next($request);
    }
}
