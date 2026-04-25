<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mesa extends Model
{
    protected $table = 'mesas';

    protected $fillable = ['sector_id', 'name', 'capacity', 'is_active', 'status'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function sector(): BelongsTo
    {
        return $this->belongsTo(Sector::class, 'sector_id');
    }

    public function cuenta(): HasOne
    {
        return $this->hasOne(Cuenta::class)->where('status', 'abierta');
    }

    public function isOcupada(): bool
    {
        return $this->status === 'ocupada';
    }

    public function canDelete(): bool
    {
        return !$this->isOcupada();
    }
}
