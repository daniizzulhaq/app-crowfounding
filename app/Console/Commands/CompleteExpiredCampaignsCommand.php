<?php

namespace App\Console\Commands;

use App\Actions\Campaigns\CompleteExpiredCampaigns;
use Illuminate\Console\Command;

class CompleteExpiredCampaignsCommand extends Command
{
    protected $signature = 'campaigns:complete-expired';

    protected $description = 'Ubah campaign aktif yang melewati deadline menjadi selesai';

    public function handle(CompleteExpiredCampaigns $action): int
    {
        $count = $action->handle();
        $this->info("{$count} campaign ditandai selesai.");

        return self::SUCCESS;
    }
}
