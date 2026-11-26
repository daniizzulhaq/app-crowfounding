<?php

namespace App\Models;

use App\Enums\DonationStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donation extends Model
{
    use HasFactory;

    public const ANONYMOUS_NAME = 'Hamba Allah';

    /**
     * status, paid_at, payment_method, midtrans_payload are NOT fillable:
     * only the payment notification action may change them.
     */
    protected $fillable = [
        'campaign_id', 'order_id', 'donor_name', 'is_anonymous', 'phone',
        'amount', 'prayer', 'expires_at', 'quantity', 'calculation',
    ];

    /** Mirror DB defaults so fresh instances are consistent before refresh(). */
    protected $attributes = [
        'status' => 'pending',
        'is_anonymous' => false,
        'prayer_hidden' => false,
    ];

    /** Never leak contact & gateway data when serialized. */
    protected $hidden = ['phone', 'midtrans_payload', 'snap_token'];

    protected function casts(): array
    {
        return [
            'status' => DonationStatus::class,
            'payment_method' => PaymentMethod::class,
            'is_anonymous' => 'boolean',
            'prayer_hidden' => 'boolean',
            'amount' => 'integer',
            'quantity' => 'integer',
            'calculation' => 'array',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
            'midtrans_payload' => 'array',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    // ---- Scopes ----------------------------------------------------------

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', DonationStatus::Paid);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', DonationStatus::Pending);
    }

    public function scopeGeneralFund(Builder $query): Builder
    {
        return $query->whereNull('campaign_id');
    }

    public function scopeWithPublicPrayer(Builder $query): Builder
    {
        return $query->paid()
            ->whereNotNull('prayer')
            ->where('prayer', '!=', '')
            ->where('prayer_hidden', false);
    }

    public function scopeForPhone(Builder $query, string $normalizedPhone): Builder
    {
        return $query->where('phone', $normalizedPhone);
    }

    /** Safe columns for public lists (no phone / payload). */
    public function scopePublicColumns(Builder $query): Builder
    {
        return $query->select(['id', 'campaign_id', 'donor_name', 'is_anonymous', 'amount', 'prayer', 'prayer_hidden', 'paid_at']);
    }

    // ---- Accessors -------------------------------------------------------

    protected function displayName(): Attribute
    {
        return Attribute::get(fn (): string => $this->is_anonymous ? self::ANONYMOUS_NAME : $this->donor_name);
    }

    protected function targetLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->campaign?->title ?? 'Sedekah umum');
    }
}
