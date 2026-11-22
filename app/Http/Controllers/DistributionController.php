<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPartials;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Distribution;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class DistributionController extends Controller
{
    use RespondsWithPartials;

    public function index(Request $request): View|Response
    {
        $campaignSlug = is_string($request->query('campaign')) ? $request->query('campaign') : null;
        $categorySlug = is_string($request->query('kategori')) ? $request->query('kategori') : null;

        $distributions = Distribution::query()
            ->forCard()
            ->forCampaign($campaignSlug)
            ->inCategory($categorySlug)
            ->latestFirst()
            ->simplePaginate(10)
            ->withQueryString();

        if ($this->wantsPartial($request)) {
            return $this->partial('distributions._items', compact('distributions'), $distributions);
        }

        return view('distributions.index', [
            'distributions' => $distributions,
            'categories' => Category::query()->ordered()->get(['id', 'name', 'slug']),
            'campaign' => $campaignSlug ? Campaign::query()->published()->where('slug', $campaignSlug)->first(['id', 'title', 'slug']) : null,
            'categorySlug' => $categorySlug,
            'totalDistributed' => (int) Distribution::query()->sum('amount'),
        ]);
    }

    public function show(Distribution $distribution): View
    {
        $distribution->load(['campaign:id,title,slug,status,published_at', 'media']);

        return view('distributions.show', [
            'distribution' => $distribution,
            'photos' => $distribution->getMedia('photos'),
        ]);
    }
}
