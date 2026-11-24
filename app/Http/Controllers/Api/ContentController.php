<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Distribution;
use App\Models\Donation;
use App\Models\MonthlyReport;
use App\Queries\HomePageData;
use App\Services\Settings;
use App\Support\MobileData;
use App\Support\ProgramImages;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ContentController extends Controller
{
    public function settings(Settings $settings)
    {
        return response()->json(['data' => $this->publicSettings($settings)]);
    }

    private function publicSettings(Settings $settings): array
    {
        return [
            'foundation_name' => $settings->foundationName(), 'tagline' => $settings->get('tagline'),
            'logo_url' => $settings->logoUrl(), 'about' => $settings->get('about'),
            'pub_permit_number' => $settings->get('pub_permit_number'),
            'whatsapp_url' => $settings->whatsappLink(), 'email' => $settings->get('email'),
            'address' => $settings->get('address'), 'donation' => config('alkiroma.donation'),
            'otp_length' => config('alkiroma.otp.length'),
            'hero_image_url' => ProgramImages::url(ProgramImages::all()[0]),
            'program_banners' => ProgramImages::banners(),
            'terms_url' => route('pages.terms'), 'privacy_url' => route('pages.privacy'),
            'web_url' => url('/'),
        ];
    }

    public function home(HomePageData $home, Settings $settings)
    {
        $data = $home->get();

        return response()->json(['data' => [
            'settings' => $this->publicSettings($settings),
            'categories' => $data['categories']->map->only(['id', 'name', 'slug', 'icon']),
            'banners' => $data['banners']->filter(fn ($banner) => ! $banner->campaign_id || Campaign::query()->published()->whereKey($banner->campaign_id)->exists())
                ->map(fn ($banner) => ['title' => $banner->title, 'image_url' => $banner->imageUrl(),
                    'campaign_slug' => $banner->campaign?->slug, 'url' => $banner->link()])->values(),
            'featured' => $data['featured']->map(fn ($campaign) => MobileData::campaign($campaign)),
            'latest' => $data['latest']->map(fn ($campaign) => MobileData::campaign($campaign)),
            'distributions' => $data['distributions']->map(fn ($distribution) => MobileData::distribution($distribution)),
            'prayers' => $data['prayers']->map(fn ($donation) => MobileData::donor($donation)),
        ]]);
    }

    public function categories()
    {
        return response()->json(['data' => Category::query()->ordered()->get(['id', 'name', 'slug', 'icon'])]);
    }

    public function campaigns(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'], 'kategori' => ['nullable', 'string', 'max:100'],
            'urut' => ['nullable', Rule::in(array_keys(Campaign::SORTS))],
            'accepting' => ['nullable', 'boolean'], 'page' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = Campaign::query()->published()->forCard()->search($filters['q'] ?? null)
            ->inCategory($filters['kategori'] ?? null)->sortBy($filters['urut'] ?? null);
        if ($request->boolean('accepting')) {
            $query->acceptingDonations();
        }

        return response()->json(MobileData::page($query->paginate(10), fn ($campaign) => MobileData::campaign($campaign)));
    }

    public function campaign(Campaign $campaign)
    {
        abort_unless($campaign->isPubliclyVisible(), 404);
        $campaign->load(['category', 'media']);

        return response()->json(['data' => MobileData::campaign($campaign, true)]);
    }

    public function donors(Campaign $campaign)
    {
        abort_unless($campaign->isPubliclyVisible(), 404);

        return response()->json(MobileData::page($campaign->donations()->paid()->publicColumns()
            ->latest('paid_at')->orderByDesc('id')->paginate(15), fn ($donation) => MobileData::donor($donation)));
    }

    public function prayers()
    {
        return response()->json(MobileData::page(Donation::query()->withPublicPrayer()->publicColumns()
            ->latest('paid_at')->orderByDesc('id')->paginate(12), fn ($donation) => MobileData::donor($donation)));
    }

    public function distributions(Request $request)
    {
        $filters = $request->validate(['campaign' => ['nullable', 'string', 'max:100'], 'kategori' => ['nullable', 'string', 'max:100']]);

        return response()->json(MobileData::page(Distribution::query()->with(['campaign', 'media'])
            ->forCampaign($filters['campaign'] ?? null)->inCategory($filters['kategori'] ?? null)
            ->latestFirst()->paginate(10), fn ($distribution) => MobileData::distribution($distribution)));
    }

    public function distribution(Distribution $distribution)
    {
        return response()->json(['data' => MobileData::distribution($distribution->load(['campaign', 'media']), true)]);
    }

    public function reports()
    {
        return response()->json(MobileData::page(MonthlyReport::query()->published()->latestPeriod()->paginate(12),
            fn ($report) => MobileData::report($report)));
    }

    public function report(MonthlyReport $report)
    {
        abort_unless($report->isPublished(), 404);

        return response()->json(['data' => MobileData::report($report)]);
    }

    public function reportDonors(MonthlyReport $report)
    {
        abort_unless($report->isPublished(), 404);

        return response()->json(MobileData::page($report->publicDonations()->paginate(20), fn ($donation) => MobileData::donor($donation)));
    }
}
