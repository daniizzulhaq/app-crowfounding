<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithPartials;
use App\Http\Requests\CampaignIndexRequest;
use App\Models\Campaign;
use App\Models\Category;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * "Donasi" flow step 1: pick which campaign to donate to (or the general fund),
 * then continue to the donation form and payment.
 */
class ChooseCampaignController extends Controller
{
    use RespondsWithPartials;

    public function __invoke(CampaignIndexRequest $request): View|Response
    {
        $filters = $request->validated();
        $min = (int) config('alkiroma.donation.min_amount');
        $nominal = (int) $request->integer('nominal');
        $nominal = $nominal >= $min ? min($nominal, (int) config('alkiroma.donation.max_amount')) : null;

        $campaigns = Campaign::query()
            ->acceptingDonations()
            ->forCard()
            ->search($filters['q'])
            ->inCategory($filters['kategori'])
            ->sortBy($filters['urut'])
            ->simplePaginate(10)
            ->withQueryString();

        $data = compact('campaigns', 'nominal');

        if ($this->wantsPartial($request)) {
            return $this->partial('donations._choose-items', $data, $campaigns);
        }

        return view('donations.choose', $data + [
            'filters' => $filters,
            'categories' => Category::query()->ordered()->get(['id', 'name', 'slug']),
        ]);
    }
}
