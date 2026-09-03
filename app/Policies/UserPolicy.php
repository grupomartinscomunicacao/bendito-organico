<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Staff management is administrator-only, with two extra guardrails: nobody
 * can delete or deactivate themselves out of the panel.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessPanel() && $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->canAccessPanel() && $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->canAccessPanel() && $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->canAccessPanel() && $user->isAdmin();
    }

    public function delete(User $user, User $model): bool
    {
        return $user->canAccessPanel()
            && $user->isAdmin()
            && ! $user->is($model);
    }
}
