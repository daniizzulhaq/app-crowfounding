<?php

namespace App\Models;

use App\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class MonthlyReport extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'draft'];

    protected $fillable = [
        'year', 'month', 'opening_balance', 'total_in', 'total_operational', 'total_distributed',
        'closing_balance', 'donation_count', 'operational_fee_percent', 'breakdown', 'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'breakdown' => 'array',
            'year' => 'integer',
            'month' => 'integer',
            'opening_balance' => 'integer',
            'total_in' => 'integer',
            'total_operational' => 'integer',
            'total_distributed' => 'integer',
            'closing_balance' => 'integer',
            'donation_count' => 'integer',
            'operational_fee_percent' => 'integer',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKey(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        if (! preg_match('/^(\d{4})-(\d{2})$/', (string) $value, $m)) {
            return null;
        }

        return static::query()->where('year', (int) $m[1])->where('month', (int) $m[2])->first();
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ReportStatus::Published);
    }

    public function scopeLatestPeriod(Builder $query): Builder
    {
        return $query->orderByDesc('year')->orderByDesc('month');
    }

    protected function periodLabel(): Attribute
    {
        return Attribute::get(fn (): string => $this->periodStart()->translatedFormat('F Y'));
    }

    public function periodStart(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfDay();
    }

    /**
     * Paid donations in this period, public-safe columns only (no phone).
     * Anonymous donors are shown as "Hamba Allah" via Donation::display_name.
     */
    public function publicDonations(): Builder
    {
        return Donation::query()
            ->paid()
            ->whereBetween('paid_at', [$this->periodStart(), $this->periodStart()->endOfMonth()])
            ->publicColumns()
            ->with('campaign:id,title,slug')
            ->orderBy('paid_at')
            ->orderBy('id');
    }

    public function isPublished(): bool
    {
        return $this->status === ReportStatus::Published;
    }

    public function pdfFileName(): string
    {
        return sprintf('laporan-alkiroma-amanah-%04d-%02d.pdf', $this->year, $this->month);
    }
}
