<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessPanel();
    }

    public function view(User $user, Order $order): bool
    {
        return $user->canAccessPanel();
    }

    public function update(User $user, Order $order): bool
    {
        return $user->canAccessPanel();
    }

    public function print(User $user, Order $order): bool
    {
        return $user->canAccessPanel();
    }

    /**
     * Orders are financial records: they get cancelled, not erased, and only
     * an administrator can remove one outright.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->canAccessPanel() && $user->isAdmin();
    }
}
