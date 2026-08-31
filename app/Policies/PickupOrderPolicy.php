<?php

namespace App\Policies;

use App\Models\User;

class PickupOrderPolicy
{
    private const ROLES = ['super_admin', 'admin', 'supervisor', 'warehouse_operator'];

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(self::ROLES);
    }

    public function view(User $user): bool
    {
        return $user->hasAnyRole(self::ROLES);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(self::ROLES);
    }

    public function update(User $user): bool
    {
        return $user->hasAnyRole(self::ROLES);
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
