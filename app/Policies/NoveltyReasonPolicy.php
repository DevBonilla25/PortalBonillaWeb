<?php

namespace App\Policies;

use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class NoveltyReasonPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NoveltyReason');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:NoveltyReason');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NoveltyReason');
    }

    public function update(AuthUser $authUser): bool
    {
        return $authUser->can('Update:NoveltyReason');
    }

    public function delete(AuthUser $authUser): bool
    {
        return $authUser->can('Delete:NoveltyReason');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NoveltyReason');
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:NoveltyReason');
    }

    public function forceDelete(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDelete:NoveltyReason');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NoveltyReason');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NoveltyReason');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:NoveltyReason');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NoveltyReason');
    }
}
