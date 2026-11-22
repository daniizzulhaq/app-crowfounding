<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPartials;
use App\Http\Requests\CampaignIndexRequest;
use App\Models\Campaign;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CampaignController extends Controller
{
    use RespondsWithPartials;

    public function index(CampaignIndexRequest $request): View|Response
    {
        $filters = $request->validated();

        $campaigns = Campaign::query()
            ->published()
            ->forCard()
            ->search($filters['q'])
            ->inCategory($filters['kategori'])
            ->sortBy($filters['urut'])
            ->simplePaginate(10)
            ->withQueryString();

        if ($this->wantsPartial($request)) {
            return $this->partial('campaigns._items', compact('campaigns'), $campaigns);
        }

        return view('campaigns.index', [
            'campaigns' => $campaigns,
            'categories' => Category::query()->ordered()->get(['id', 'name', 'slug']),
            'filters' => $filters,
            'sorts' => Campaign::SORTS,
        ]);
    }

    public function show(Campaign $campaign): View
    {
        abort_unless($campaign->isPubliclyVisible(), 404);

        $campaign->load(['category:id,name,slug', 'media']);

        return view('campaigns.show', [
            'campaign' => $campaign,
            'distributions' => $campaign->distributions()->forCard()->latestFirst()->limit(5)->get(),
            'donors' => $campaign->donations()->paid()->publicColumns()->latest('paid_at')->simplePaginate(10, pageName: 'p'),
            'prayerCount' => $campaign->donations()->withPublicPrayer()->count(),
        ]);
    }

    /** Infinite list of donors & prayers for the detail tab. */
    public function donors(Request $request, Campaign $campaign): Response
    {
        abort_unless($campaign->isPubliclyVisible(), 404);

        $donors = $campaign->donations()->paid()->publicColumns()->latest('paid_at')
            ->simplePaginate(10, pageName: 'p')
            ->withPath(route('campaigns.donors', $campaign));

        return $this->partial('campaigns._donors', compact('donors'), $donors);
    }
}
