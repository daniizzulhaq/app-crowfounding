<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

class MobileDonorToken extends Model
{
    use Prunable;

    protected $fillable = ['phone', 'token_hash', 'expires_at'];

    protected $hidden = ['phone', 'token_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    public function prunable()
    {
        return static::where('expires_at', '<=', now());
    }
}
