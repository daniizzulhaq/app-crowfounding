<?php

namespace App\Policies;

use App\Models\Donation;
use App\Models\User;

/**
 * Donations are read-only in admin: money data only changes via the Midtrans pipeline.
 */
class DonationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Donation $donation): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Donation $donation): bool
    {
        return false;
    }

    public function delete(User $user, Donation $donation): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    /** Hide/show prayers & ask Midtrans for status. */
    public function moderate(User $user, Donation $donation): bool
    {
        return $user->is_admin;
    }
}
