<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class OtpCode extends Model
{
    use Prunable;

    protected $fillable = ['phone', 'code_hash', 'expires_at'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'expires_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public function scopeUsableFor(Builder $query, string $phone): Builder
    {
        return $query->where('phone', $phone)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now());
    }

    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDay());
    }
}
