<?php

namespace App\Actions\Reports;

use App\Enums\ReportStatus;
use App\Models\MonthlyReport;
use App\Services\Settings;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PublishMonthlyReport
{
    public function __construct(private readonly Settings $settings) {}

    public function handle(MonthlyReport $report): MonthlyReport
    {
        $path = 'reports/'.$report->pdfFileName();

        Storage::disk('local')->put($path, $this->renderPdf($report));

        $report->forceFill([
            'status' => ReportStatus::Published,
            'pdf_path' => $path,
            'published_at' => now(),
        ])->save();

        return $report;
    }

    public function renderPdf(MonthlyReport $report): string
    {
        return Pdf::loadView('pdf.monthly-report', [
            'report' => $report,
            'settings' => $this->settings,
        ])->setPaper('a4')->output();
    }
}
