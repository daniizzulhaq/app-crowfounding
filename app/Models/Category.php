<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory, HasUniqueSlug;

    protected $fillable = ['name', 'slug', 'icon', 'sort_order', 'donation_type', 'unit_label', 'unit_price', 'zakat_rate', 'zakat_nisab'];

    protected function casts(): array
    {
        return ['unit_price' => 'integer', 'zakat_nisab' => 'integer', 'zakat_rate' => 'decimal:2'];
    }

    protected function slugSource(): string
    {
        return 'name';
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
