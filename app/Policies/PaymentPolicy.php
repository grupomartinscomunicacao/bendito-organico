<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessPanel();
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->canAccessPanel();
    }

    /**
     * Re-reading a transaction from Mercado Pago touches order state, so it
     * stays with administrators.
     */
    public function sync(User $user, Payment $payment): bool
    {
        return $user->canAccessPanel() && $user->isAdmin();
    }
}
