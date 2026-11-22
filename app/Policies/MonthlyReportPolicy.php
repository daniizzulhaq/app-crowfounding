<?php

namespace App\Policies;

use App\Models\MonthlyReport;
use App\Models\User;

class MonthlyReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin;
    }

    public function view(User $user, MonthlyReport $report): bool
    {
        return $user->is_admin;
    }

    /** Reports are generated from data, never typed in by hand. */
    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MonthlyReport $report): bool
    {
        return false;
    }

    public function generate(User $user): bool
    {
        return $user->is_admin;
    }

    public function publish(User $user, MonthlyReport $report): bool
    {
        return $user->is_admin && ! $report->isPublished();
    }

    public function delete(User $user, MonthlyReport $report): bool
    {
        return $user->is_admin && ! $report->isPublished();
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
