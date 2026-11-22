<?php

namespace App\Actions\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Queries\HomePageData;

class CompleteExpiredCampaigns
{
    public function handle(): int
    {
        $count = Campaign::query()
            ->active()
            ->whereNotNull('deadline')
            ->whereDate('deadline', '<', today())
            ->update(['status' => CampaignStatus::Completed, 'updated_at' => now()]);

        if ($count > 0) {
            HomePageData::forget();
        }

        return $count;
    }
}
