<?php

namespace App\Console\Commands;

use App\Actions\Donations\ExpirePendingDonations;
use Illuminate\Console\Command;

class ExpirePendingDonationsCommand extends Command
{
    protected $signature = 'donations:expire';

    protected $description = 'Tandai donasi pending yang lewat batas waktu sebagai kedaluwarsa';

    public function handle(ExpirePendingDonations $action): int
    {
        $count = $action->handle();
        $this->info("{$count} donasi ditandai kedaluwarsa.");

        return self::SUCCESS;
    }
}
