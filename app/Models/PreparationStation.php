<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreparationStation extends Model
{
    protected $table = 'preparation_stations';

    protected $fillable = [
        'name',
        'icon',
        'color',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function ingredients(): HasMany
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * Users assigned to this preparation station.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'preparation_station_user',
            'preparation_station_id',
            'user_id'
        )->withTimestamps();
    }
}
