<?php

namespace App\Queries;

use App\Models\Banner;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Distribution;
use App\Models\Donation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * All home page data in one cached bundle (short TTL, flushed on content changes).
 */
class HomePageData
{
    public const CACHE_KEY = 'home.v2';

    /**
     * @return array{banners: Collection, categories: Collection, featured: Collection, latest: Collection, distributions: Collection, prayers: Collection}
     */
    public function get(): array
    {
        return Cache::remember(self::CACHE_KEY, (int) config('alkiroma.home_cache_seconds'), fn () => [
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'banners' => Banner::query()->active()->with('campaign:id,slug')->limit(6)->get(),
            'featured' => Campaign::query()->published()->active()->featured()->forCard()
                ->orderByDesc('published_at')->limit(8)->get(),
            'latest' => Campaign::query()->published()->active()->forCard()
                ->orderByDesc('published_at')->limit(6)->get(),
            'distributions' => Distribution::query()->forCard()->latestFirst()->limit(3)->get(),
            'prayers' => Donation::query()->withPublicPrayer()->publicColumns()
                ->with('campaign:id,title,slug')->latest('paid_at')->limit(5)->get(),
        ]);
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
