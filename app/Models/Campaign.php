<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\DonationStatus;
use App\Models\Concerns\HasUniqueSlug;
use App\Support\ProgramImages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Campaign extends Model implements HasMedia
{
    use HasFactory, HasUniqueSlug, InteractsWithMedia;

    public const SORTS = [
        'terbaru' => 'Terbaru',
        'segera-berakhir' => 'Segera berakhir',
        'terkumpul-terbanyak' => 'Terkumpul terbanyak',
    ];

    protected $attributes = [
        'status' => 'draft',
        'collected_amount' => 0,
        'distributed_amount' => 0,
        'donor_count' => 0,
        'is_featured' => false,
    ];

    /**
     * collected_amount, distributed_amount & donor_count are NOT fillable:
     * they are only changed by payment / distribution actions.
     */
    protected $fillable = [
        'category_id', 'title', 'slug', 'summary', 'story', 'target_amount',
        'deadline', 'status', 'is_featured', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CampaignStatus::class,
            'is_featured' => 'boolean',
            'deadline' => 'date',
            'published_at' => 'datetime',
            'target_amount' => 'integer',
            'collected_amount' => 'integer',
            'distributed_amount' => 'integer',
            'donor_count' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // "Langsung tayang": first time it becomes active, stamp published_at.
        static::saving(function (Campaign $campaign) {
            if ($campaign->target_amount !== null && $campaign->target_amount <= 0) {
                $campaign->target_amount = null;
            }
            if ($campaign->status === CampaignStatus::Active && $campaign->published_at === null) {
                $campaign->published_at = now();
            }
        });
    }

    // ---- Relations -------------------------------------------------------

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(Distribution::class);
    }

    // ---- Scopes ----------------------------------------------------------

    /** Visible on the public site. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereIn('status', [CampaignStatus::Active, CampaignStatus::Completed])
            ->whereNotNull('published_at');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::Active);
    }

    /** Active, published and not past the deadline: can receive donations now. */
    public function scopeAcceptingDonations(Builder $query): Builder
    {
        return $query->published()
            ->active()
            ->where(fn (Builder $q) => $q->whereNull('deadline')->orWhereDate('deadline', '>=', today()));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        $term = trim((string) $term);

        return $query->when($term !== '', fn (Builder $q) => $q->where(function (Builder $q) use ($term) {
            $like = '%'.addcslashes($term, '%_\\').'%';
            $q->where('title', 'like', $like)->orWhere('summary', 'like', $like);
        }));
    }

    public function scopeInCategory(Builder $query, ?string $categorySlug): Builder
    {
        return $query->when($categorySlug, fn (Builder $q) => $q->whereHas(
            'category',
            fn (Builder $c) => $c->where('slug', $categorySlug)
        ));
    }

    public function scopeSortBy(Builder $query, ?string $sort): Builder
    {
        return match ($sort) {
            'segera-berakhir' => $query->active()
                ->whereNotNull('deadline')
                ->whereDate('deadline', '>=', today())
                ->orderBy('deadline')
                ->orderByDesc('id'),
            'terkumpul-terbanyak' => $query->orderByDesc('collected_amount')->orderByDesc('id'),
            default => $query->orderByDesc('published_at')->orderByDesc('id'),
        };
    }

    /** Columns needed for cards & lists (keeps the long story out of list queries). */
    public function scopeForCard(Builder $query): Builder
    {
        return $query->select([
            'id', 'category_id', 'title', 'slug', 'summary', 'target_amount', 'collected_amount',
            'distributed_amount', 'donor_count', 'deadline', 'status', 'is_featured', 'published_at',
        ])->with(['category', 'media'])->withSum(['donations as paid_units' => fn (Builder $q) => $q->where('status', DonationStatus::Paid)], 'quantity');
    }

    // ---- Accessors -------------------------------------------------------

    public function hasTarget(): bool
    {
        return (int) ($this->target_amount ?? 0) > 0;
    }

    protected function progressPercent(): Attribute
    {
        return Attribute::get(function (): int {
            if (! $this->hasTarget()) {
                return 0;
            }

            return (int) min(100, intdiv($this->collected_amount * 100, $this->target_amount));
        });
    }

    protected function daysLeft(): Attribute
    {
        return Attribute::get(function (): ?int {
            if ($this->deadline === null) {
                return null;
            }

            return max(0, (int) today()->diffInDays($this->deadline, false));
        });
    }

    protected function balance(): Attribute
    {
        return Attribute::get(fn (): int => max(0, $this->collected_amount - $this->distributed_amount));
    }

    public function acceptsDonations(): bool
    {
        if (! $this->status->acceptsDonations()) {
            return false;
        }

        return $this->deadline === null || $this->deadline->endOfDay()->isFuture();
    }

    public function isPubliclyVisible(): bool
    {
        return in_array($this->status, [CampaignStatus::Active, CampaignStatus::Completed], true)
            && $this->published_at !== null;
    }

    public function paidDonations(): HasMany
    {
        return $this->donations()->where('status', DonationStatus::Paid);
    }

    // ---- Media -----------------------------------------------------------

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 240, 180)->format('webp');
        $this->addMediaConversion('card')->fit(Fit::Crop, 800, 450)->format('webp');
    }

    /** Owner-supplied program artwork shared by the website and mobile API. */
    public function programImage(): array
    {
        return ProgramImages::forSubject($this->title.' '.$this->category?->name);
    }

    public function programCoverUrl(): string
    {
        return ProgramImages::url($this->programImage());
    }

    public function coverUrl(string $conversion = 'card'): ?string
    {
        $media = $this->getFirstMedia('cover');

        if ($media === null) {
            return null;
        }

        return $media->hasGeneratedConversion($conversion)
            ? $media->getUrl($conversion)
            : $media->getUrl();
    }
}
