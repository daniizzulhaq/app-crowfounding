<?php

namespace App\Policies;

use App\Models\Campaign;
use App\Models\User;

class CampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, Campaign $campaign): bool
    {
        return $user->is_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_admin;
    }

    public function update(User $user, Campaign $campaign): bool
    {
        return $user->is_admin;
    }

    /** A campaign with donations or distributions is history: close it instead. */
    public function delete(User $user, Campaign $campaign): bool
    {
        // Uses withCount() values when the table preloaded them (avoids N+1).
        $donations = $campaign->donations_count ?? $campaign->donations()->count();
        $distributions = $campaign->distributions_count ?? $campaign->distributions()->count();

        return $user->is_admin && $donations === 0 && $distributions === 0;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
