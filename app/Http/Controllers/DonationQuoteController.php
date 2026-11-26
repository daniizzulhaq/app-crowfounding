<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\DonationCalculator;
use Illuminate\Http\Request;

class DonationQuoteController extends Controller
{
    public function __invoke(Request $request, DonationCalculator $calculator)
    {
        $data = $request->validate(DonationCalculator::rules());
        $campaign = ! empty($data['campaign']) ? Campaign::where('slug', $data['campaign'])->firstOrFail() : null;
        abort_if($campaign && (! $campaign->isPubliclyVisible() || ! $campaign->acceptsDonations()), 422, 'Program tidak menerima donasi.');

        return response()->json(['data' => $calculator->quote($campaign, $data)])->header('Cache-Control', 'no-store');
    }
}
