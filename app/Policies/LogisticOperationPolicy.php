<?php

namespace App\Policies;

use App\Models\User;

class LogisticOperationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ViewAny:LogisticOperation');
    }

    public function view(User $user): bool
    {
        return $user->can('View:LogisticOperation');
    }

    public function create(User $user): bool
    {
        return $user->can('Create:LogisticOperation');
    }

    public function update(User $user): bool
    {
        return $user->can('Update:LogisticOperation');
    }

    public function delete(User $user): bool
    {
        return false;
    }
}
