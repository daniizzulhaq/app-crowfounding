<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Distribution;
use App\Models\MonthlyReport;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', now()->addHour(), fn () => view('sitemap', [
            'campaigns' => Campaign::query()->published()->get(['id', 'slug', 'updated_at']),
            'distributions' => Distribution::query()->get(['id', 'slug', 'updated_at']),
            'reports' => MonthlyReport::query()->published()->get(['id', 'year', 'month', 'updated_at']),
        ])->render());

        return response($xml, 200, ['Content-Type' => 'application/xml']);
    }
}
