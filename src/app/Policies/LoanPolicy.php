<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Loan;
use App\Models\User;

class LoanPolicy
{
    /**
     * Determine whether the user can view any loans.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isLibrarian();
    }

    /**
     * Determine whether the user can view the loan.
     */
    public function view(User $user, Loan $loan): bool
    {
        // Admin and librarian can view all loans
        if ($user->isAdmin() || $user->isLibrarian()) {
            return true;
        }

        // Members can only view their own loans
        return $user->id === $loan->user_id;
    }

    /**
     * Determine whether the user can create loans.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isLibrarian();
    }

    /**
     * Determine whether the user can update the loan.
     */
    public function update(User $user, Loan $loan): bool
    {
        // Only admin and librarian can update loans
        return $user->isAdmin() || $user->isLibrarian();
    }

    /**
     * Determine whether the user can delete the loan.
     */
    public function delete(User $user, Loan $loan): bool
    {
        // Only admin can delete loans
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the loan.
     */
    public function restore(User $user, Loan $loan): bool
    {
        return $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the loan.
     */
    public function forceDelete(User $user, Loan $loan): bool
    {
        return $user->isAdmin();
    }
}
