<?php

namespace App\Actions\Distributions;

use App\Models\Campaign;
use App\Models\Distribution;
use App\Models\Donation;
use App\Queries\HomePageData;
use Illuminate\Support\Facades\DB;

/**
 * Records distributions and keeps campaigns.distributed_amount in sync.
 * Rule: total distributed may never exceed what was collected
 * (per campaign, or for the general fund when campaign_id is null).
 */
class DistributionLedger
{
    /**
     * @param  array<string, mixed>  $attributes  title, distributed_at, location, recipient, body
     *
     * @throws InsufficientFundsException
     */
    public function create(array $attributes, ?int $campaignId, int $amount): Distribution
    {
        return $this->guarded(function () use ($attributes, $campaignId, $amount) {
            $this->lockCampaigns([$campaignId]);
            $this->assertAvailable($campaignId, $amount);

            $distribution = new Distribution($attributes);
            $distribution->forceFill(['campaign_id' => $campaignId, 'amount' => $amount])->save();

            $this->adjust($campaignId, $amount);

            return $distribution;
        });
    }

    /**
     * @throws InsufficientFundsException
     */
    public function update(Distribution $distribution, array $attributes, ?int $campaignId, int $amount): Distribution
    {
        return $this->guarded(function () use ($distribution, $attributes, $campaignId, $amount) {
            $fresh = Distribution::query()->lockForUpdate()->findOrFail($distribution->getKey());
            $oldCampaignId = $fresh->campaign_id;
            $oldAmount = $fresh->amount;

            $this->lockCampaigns([$oldCampaignId, $campaignId]);

            // Give back the old amount first, then check the new amount.
            $this->adjust($oldCampaignId, -$oldAmount);
            $this->assertAvailable($campaignId, $amount, excludeId: $fresh->id);

            $distribution->fill($attributes);
            $distribution->forceFill(['campaign_id' => $campaignId, 'amount' => $amount])->save();

            $this->adjust($campaignId, $amount);

            return $distribution;
        });
    }

    public function delete(Distribution $distribution): void
    {
        $this->guarded(function () use ($distribution) {
            $fresh = Distribution::query()->lockForUpdate()->findOrFail($distribution->getKey());
            $this->lockCampaigns([$fresh->campaign_id]);
            $this->adjust($fresh->campaign_id, -$fresh->amount);
            $distribution->delete();
        });
    }

    public function available(?int $campaignId, ?int $excludeId = null): int
    {
        if ($campaignId !== null) {
            $campaign = Campaign::query()->select(['id', 'collected_amount', 'distributed_amount'])->findOrFail($campaignId);

            return max(0, $campaign->collected_amount - $campaign->distributed_amount);
        }

        $collected = (int) Donation::query()->paid()->generalFund()->sum('amount');
        $distributed = (int) Distribution::query()
            ->whereNull('campaign_id')
            ->when($excludeId, fn ($q) => $q->whereKeyNot($excludeId))
            ->sum('amount');

        return max(0, $collected - $distributed);
    }

    /**
     * Balance available for a form, counting the edited distribution's own amount as free.
     */
    public function availableFor(?int $campaignId, ?Distribution $editing = null): int
    {
        $available = $this->available($campaignId, $editing?->id);

        if ($editing !== null && $campaignId !== null && $editing->campaign_id === $campaignId) {
            $available += $editing->amount;
        }

        return $available;
    }

    private function assertAvailable(?int $campaignId, int $amount, ?int $excludeId = null): void
    {
        $available = $this->available($campaignId, $excludeId);

        if ($amount > $available) {
            throw new InsufficientFundsException($available);
        }
    }

    private function adjust(?int $campaignId, int $delta): void
    {
        if ($campaignId === null || $delta === 0) {
            return;
        }

        $query = Campaign::query()->whereKey($campaignId);

        $delta > 0
            ? $query->increment('distributed_amount', $delta)
            : $query->decrement('distributed_amount', -$delta);
    }

    /**
     * Lock campaign rows in a stable order to avoid deadlocks.
     * For the general fund we lock the general-fund distribution rows instead.
     */
    private function lockCampaigns(array $campaignIds): void
    {
        $ids = array_values(array_unique(array_filter($campaignIds, fn ($id) => $id !== null)));
        sort($ids);

        if ($ids !== []) {
            Campaign::query()->whereKey($ids)->orderBy('id')->lockForUpdate()->get(['id']);
        }

        if (in_array(null, $campaignIds, true)) {
            Distribution::query()->whereNull('campaign_id')->lockForUpdate()->get(['id']);
        }
    }

    private function guarded(callable $callback): mixed
    {
        $result = DB::transaction($callback);
        HomePageData::forget();

        return $result;
    }
}
