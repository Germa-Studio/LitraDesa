<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, User $member): bool
    {
        // Admins can view any member, members can only view themselves
        return $user->isAdmin() || $user->id === $member->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(?User $user): bool
    {
        // Anyone can register (even guests)
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, User $member): bool
    {
        // Members can only update their own profile
        return $user->id === $member->id && $user->isMember();
    }

    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, User $member): bool
    {
        // Only admins can approve members
        return $user->isAdmin() && $member->isMember();
    }

    /**
     * Determine whether the user can suspend the model.
     */
    public function suspend(User $user, User $member): bool
    {
        // Only admins can suspend members
        return $user->isAdmin() && $member->isMember();
    }

    /**
     * Determine whether the user can reactivate the model.
     */
    public function reactivate(User $user, User $member): bool
    {
        // Only admins can reactivate members
        return $user->isAdmin() && $member->isMember();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, User $member): bool
    {
        // Only admins can delete members, but not themselves
        return $user->isAdmin() && $user->id !== $member->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, User $member): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, User $member): bool
    {
        return $user->isAdmin();
    }
}

// Made with Bob
