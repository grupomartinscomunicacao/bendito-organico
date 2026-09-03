<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email', 'max:180'],
        ], [], ['email' => 'e-mail']);

        Password::sendResetLink($request->only('email'));

        // Always the same answer, whether or not the address exists: the reply
        // itself must not tell an attacker which accounts are real.
        return back()->with(
            'success',
            'Se este e-mail estiver cadastrado, você receberá o link de redefinição em instantes.'
        );
    }
}
