<?php

namespace App\Policies;

use App\Models\Distribution;
use App\Models\User;

class DistributionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Distribution $distribution): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Distribution $distribution): bool
    {
        return $user->is_admin;
    }

    public function delete(User $user, Distribution $distribution): bool
    {
        return $user->is_admin;
    }

    /** Bulk delete bypasses the ledger; keep it off. */
    public function deleteAny(User $user): bool
    {
        return false;
    }
}
