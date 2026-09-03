<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

/**
 * Operators run the catalog day to day; only administrators may destroy a
 * record permanently.
 */
class ProductPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessPanel();
    }

    public function view(User $user, Product $product): bool
    {
        return $user->canAccessPanel();
    }

    public function create(User $user): bool
    {
        return $user->canAccessPanel();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->canAccessPanel();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->canAccessPanel();
    }

    public function restore(User $user, Product $product): bool
    {
        return $user->canAccessPanel();
    }

    public function forceDelete(User $user, Product $product): bool
    {
        return $user->canAccessPanel() && $user->isAdmin();
    }
}
