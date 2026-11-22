<?php

namespace App\Console\Commands;

use App\Actions\Donations\RefreshPendingDonation;
use App\Models\Donation;
use Illuminate\Console\Command;

class SyncPendingDonationsCommand extends Command
{
    protected $signature = 'donations:sync-pending {--hours=24 : Only donations created within this many hours}';

    protected $description = 'Cek status donasi pending ke Midtrans (cadangan bila webhook tidak sampai)';

    public function handle(RefreshPendingDonation $refresh): int
    {
        $checked = 0;
        $paid = 0;

        Donation::query()
            ->pending()
            ->where('created_at', '>=', now()->subHours((int) $this->option('hours')))
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->each(function (Donation $donation) use ($refresh, &$checked, &$paid) {
                $checked++;
                if ($refresh->handle($donation)->status->value === 'paid') {
                    $paid++;
                }
            });

        $this->info("{$checked} donasi dicek, {$paid} berhasil dibayar.");

        return self::SUCCESS;
    }
}
