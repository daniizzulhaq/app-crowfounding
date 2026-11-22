<?php

namespace App\Exports;

use App\Actions\Reports\GenerateMonthlyReport;
use App\Models\MonthlyReport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Laporan PUB triwulan: ringkasan 3 bulan + rincian per campaign.
 * Uses published reports when available, otherwise builds drafts on the fly.
 */
class PubQuarterlyExport
{
    public function __construct(private readonly GenerateMonthlyReport $generator) {}

    public function download(int $year, int $quarter): BinaryFileResponse
    {
        $months = range(($quarter - 1) * 3 + 1, $quarter * 3);
        $rows = [];

        foreach ($months as $month) {
            $report = MonthlyReport::query()->where('year', $year)->where('month', $month)->first()
                ?? $this->generator->handle($year, $month);

            $rows[] = ['RINGKASAN', $report->period_label, '', $report->opening_balance, $report->total_in,
                $report->total_operational, $report->total_distributed, $report->closing_balance, $report->donation_count];

            foreach ($report->breakdown ?? [] as $line) {
                $rows[] = ['RINCIAN', $report->period_label, $line['title'], '', $line['in'], '', $line['distributed'], '', $line['donations']];
            }
        }

        return XlsxExport::download(
            sprintf('laporan-pub-%d-tw%d.xlsx', $year, $quarter),
            ['Jenis', 'Periode', 'Campaign', 'Saldo awal', 'Dana masuk', 'Operasional', 'Disalurkan', 'Saldo akhir', 'Jumlah donasi'],
            $rows,
        );
    }
}
