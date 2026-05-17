<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Softbook;
use App\Models\User;

class SoftbookPolicy
{
    /**
     * Determine whether the user can view any softbooks.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view softbooks
        return true;
    }

    /**
     * Determine whether the user can view the softbook.
     */
    public function view(User $user, Softbook $softbook): bool
    {
        // All authenticated users can view softbook details
        return true;
    }

    /**
     * Determine whether the user can create softbooks.
     */
    public function create(User $user): bool
    {
        // Only admin and librarian can upload softbooks
        return $user->isAdmin() || $user->isLibrarian();
    }

    /**
     * Determine whether the user can update the softbook.
     */
    public function update(User $user, Softbook $softbook): bool
    {
        // Only admin and librarian can update softbooks
        return $user->isAdmin() || $user->isLibrarian();
    }

    /**
     * Determine whether the user can delete the softbook.
     */
    public function delete(User $user, Softbook $softbook): bool
    {
        // Only admin can delete softbooks
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can download the softbook.
     */
    public function download(User $user, Softbook $softbook): bool
    {
        // Only active members can download
        if ($user->status !== 'active') {
            return false;
        }

        // Softbook must be active
        if (!$softbook->is_active) {
            return false;
        }

        // Check if user has remaining downloads
        return $softbook->canBeDownloadedBy($user);
    }

    /**
     * Determine whether the user can restore the softbook.
     */
    public function restore(User $user, Softbook $softbook): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the softbook.
     */
    public function forceDelete(User $user, Softbook $softbook): bool
    {
        return $user->isAdmin();
    }
}
