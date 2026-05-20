<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class DriverProfilePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:DriverProfile');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:DriverProfile');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:DriverProfile');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:DriverProfile');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:DriverProfile');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:DriverProfile');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:DriverProfile');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:DriverProfile');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:DriverProfile');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:DriverProfile');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:DriverProfile');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:DriverProfile');
    }
}
