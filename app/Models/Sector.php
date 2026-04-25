<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sector extends Model
{
    protected $table = 'sectores';

    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class, 'sector_id');
    }

    public function mesasActivas(): HasMany
    {
        return $this->hasMany(Mesa::class, 'sector_id')->where('is_active', true);
    }

    public function canDelete(): bool
    {
        return $this->mesas()->doesntExist();
    }
}
