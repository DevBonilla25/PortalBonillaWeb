<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class ZonePolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Zone');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:Zone');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Zone');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:Zone');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:Zone');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Zone');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:Zone');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:Zone');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Zone');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Zone');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:Zone');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Zone');
    }
}
