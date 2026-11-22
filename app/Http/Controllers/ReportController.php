<?php

namespace App\Http\Controllers;

use App\Actions\Reports\PublishMonthlyReport;
use App\Http\Controllers\Concerns\RespondsWithPartials;
use App\Models\MonthlyReport;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ReportController extends Controller
{
    use RespondsWithPartials;

    public function index(): View
    {
        return view('reports.index', [
            'reports' => MonthlyReport::query()->published()->latestPeriod()->paginate(12),
        ]);
    }

    public function show(MonthlyReport $report): View
    {
        abort_unless($report->isPublished(), 404);

        return view('reports.show', [
            'report' => $report,
            'donors' => $this->donorPage($report),
        ]);
    }

    /** Infinite-scroll page of donor names for a published report. */
    public function donors(MonthlyReport $report): HttpResponse
    {
        abort_unless($report->isPublished(), 404);

        $donors = $this->donorPage($report);

        return $this->partial('reports._donors', compact('donors'), $donors);
    }

    public function download(MonthlyReport $report, PublishMonthlyReport $publisher): Response
    {
        abort_unless($report->isPublished(), 404);

        if ($report->pdf_path && Storage::disk('local')->exists($report->pdf_path)) {
            return Storage::disk('local')->download($report->pdf_path, $report->pdfFileName());
        }

        // PDF missing (e.g. storage moved): render it again from the stored snapshot.
        return response($publisher->renderPdf($report), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$report->pdfFileName().'"',
        ]);
    }

    private function donorPage(MonthlyReport $report)
    {
        return $report->publicDonations()
            ->simplePaginate(20, pageName: 'p')
            ->withPath(route('reports.donors', $report));
    }
}
