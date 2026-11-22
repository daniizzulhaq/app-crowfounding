<?php

namespace App\Actions\Donations;

use App\Enums\DonationStatus;
use App\Models\Donation;

class ExpirePendingDonations
{
    /**
     * @return int Number of donations marked as expired.
     */
    public function handle(): int
    {
        return Donation::query()
            ->pending()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->update(['status' => DonationStatus::Expired, 'updated_at' => now()]);
    }
}
