<?php

namespace App\Support;

use App\Models\Campaign;
use App\Models\Distribution;
use App\Models\Donation;
use App\Models\MonthlyReport;
use App\Services\DonationCalculator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\URL;

/** Explicit API fields: model serialization must never expose donor contact or gateway payloads. */
final class MobileData
{
    public static function campaign(Campaign $campaign, bool $detail = false): array
    {
        $cover = $campaign->coverUrl();
        $program = $cover === null ? $campaign->programImage() : null;
        $data = [
            'id' => $campaign->id, 'slug' => $campaign->slug, 'title' => $campaign->title,
            'donation_config' => DonationCalculator::config($campaign->category),
            'paid_units' => (int) ($campaign->paid_units ?? ($campaign->category?->donation_type === 'unit' ? $campaign->paidDonations()->sum('quantity') : 0)),
            'summary' => $campaign->summary, 'category' => $campaign->category?->only(['id', 'name', 'slug', 'icon']),
            'cover_url' => $cover ?? $campaign->programCoverUrl(), 'is_illustration' => false,
            'cover_source' => $cover === null ? 'foundation' : 'upload',
            'cover_asset' => $program['file'] ?? null,
            'cover_aspect_ratio' => $program ? $program['width'] / $program['height'] : 16 / 9,
            'target_amount' => $campaign->target_amount,
            'has_target' => $campaign->hasTarget(),
            'collected_amount' => $campaign->collected_amount, 'distributed_amount' => $campaign->distributed_amount,
            'donor_count' => $campaign->donor_count, 'progress_percent' => $campaign->progress_percent,
            'days_left' => $campaign->days_left, 'deadline' => $campaign->deadline?->toDateString(),
            'status' => $campaign->status->value, 'accepts_donations' => $campaign->isPubliclyVisible() && $campaign->acceptsDonations(),
            'web_url' => route('campaigns.show', $campaign),
        ];
        if ($detail) {
            $data['story_html'] = Html::clean($campaign->story);
        }

        return $data;
    }

    public static function donor(Donation $donation): array
    {
        return [
            'id' => $donation->id, 'display_name' => $donation->display_name,
            'amount' => $donation->amount, 'prayer' => $donation->prayer_hidden ? null : $donation->prayer,
            'paid_at' => $donation->paid_at?->toIso8601String(),
        ];
    }

    public static function receipt(Donation $donation): array
    {
        $base = config('midtrans.is_production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';

        return [
            'order_id' => $donation->order_id, 'amount' => $donation->amount,
            'quantity' => $donation->quantity, 'calculation' => $donation->calculation,
            'status' => $donation->status->value, 'target_label' => $donation->target_label,
            'created_at' => $donation->created_at?->toIso8601String(),
            'paid_at' => $donation->paid_at?->toIso8601String(),
            'expires_at' => $donation->expires_at?->toIso8601String(),
            'finish_url' => route('donations.status', $donation->order_id),
            'payment_url' => $donation->status->value === 'pending' && $donation->snap_token
                ? $base.'/snap/v4/redirection/'.rawurlencode($donation->snap_token) : null,
            // A receipt URL is a private capability, stored only on the donor's device.
            'status_url' => URL::temporarySignedRoute('api.donations.show', now()->addDays(7), ['orderId' => $donation->order_id]),
        ];
    }

    public static function distribution(Distribution $distribution, bool $detail = false): array
    {
        $photos = $distribution->getMedia('photos')->map(fn ($media) => $distribution->photoUrl($media, 'large'))->values();
        $data = [
            'id' => $distribution->id, 'slug' => $distribution->slug, 'title' => $distribution->title,
            'amount' => $distribution->amount, 'location' => $distribution->location,
            'recipient' => $distribution->recipient, 'distributed_at' => $distribution->distributed_at?->toDateString(),
            'cover_url' => $photos->first(), 'excerpt' => $distribution->excerpt,
            'campaign_title' => $distribution->campaign?->title,
        ];
        if ($detail) {
            $data['body_html'] = Html::clean($distribution->body);
            $data['photos'] = $photos;
        }

        return $data;
    }

    public static function report(MonthlyReport $report): array
    {
        return [
            'period' => $report->getRouteKey(), 'period_label' => $report->period_label,
            'opening_balance' => $report->opening_balance, 'total_in' => $report->total_in,
            'total_operational' => $report->total_operational, 'total_distributed' => $report->total_distributed,
            'closing_balance' => $report->closing_balance, 'donation_count' => $report->donation_count,
            'operational_fee_percent' => $report->operational_fee_percent,
            'download_url' => route('reports.download', $report),
        ];
    }

    public static function page(LengthAwarePaginator $page, callable $map): array
    {
        return [
            'data' => $page->getCollection()->map($map)->values(),
            'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(), 'total' => $page->total()],
        ];
    }
}
