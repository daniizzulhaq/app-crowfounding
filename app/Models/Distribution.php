<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Distribution extends Model implements HasMedia
{
    use HasFactory, HasUniqueSlug, InteractsWithMedia;

    /** amount & campaign_id are changed only through DistributionService. */
    protected $fillable = ['title', 'slug', 'distributed_at', 'location', 'recipient', 'body'];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'distributed_at' => 'date',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function scopeLatestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('distributed_at')->orderByDesc('id');
    }

    public function scopeForCampaign(Builder $query, ?string $campaignSlug): Builder
    {
        return $query->when($campaignSlug, fn (Builder $q) => $q->whereHas(
            'campaign',
            fn (Builder $c) => $c->where('slug', $campaignSlug)
        ));
    }

    public function scopeInCategory(Builder $query, ?string $categorySlug): Builder
    {
        return $query->when($categorySlug, fn (Builder $q) => $q->whereHas(
            'campaign.category',
            fn (Builder $c) => $c->where('slug', $categorySlug)
        ));
    }

    public function scopeForCard(Builder $query): Builder
    {
        return $query->select(['id', 'campaign_id', 'title', 'slug', 'amount', 'distributed_at', 'location', 'recipient'])
            ->with(['campaign:id,title,slug', 'media']);
    }

    protected function excerpt(): Attribute
    {
        return Attribute::get(fn (): string => Str::limit(trim(strip_tags((string) $this->body)), 140));
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('photos');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 240, 180)->format('webp');
        $this->addMediaConversion('large')->fit(Fit::Max, 1200, 1200)->format('webp');
    }

    public function photoUrl(Media $media, string $conversion = 'thumb'): string
    {
        return $media->hasGeneratedConversion($conversion) ? $media->getUrl($conversion) : $media->getUrl();
    }
}
