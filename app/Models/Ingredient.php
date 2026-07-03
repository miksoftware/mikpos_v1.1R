<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'unit_id',
        'stock',
        'purchase_price',
        'sale_price',
        'includes_tax',
        'tax_id',
        'manage_inventory',
        'show_in_pos',
        'is_active',
        'preparation_station_id',
        'category_id',
    ];

    protected function casts(): array
    {
        return [
            'stock'            => 'decimal:2',
            'purchase_price'   => 'decimal:2',
            'sale_price'       => 'decimal:2',
            'includes_tax'     => 'boolean',
            'manage_inventory' => 'boolean',
            'show_in_pos'      => 'boolean',
            'is_active'        => 'boolean',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function preparationStation(): BelongsTo
    {
        return $this->belongsTo(PreparationStation::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function groups(): BelongsToMany
    {
        return $this->belongsToMany(IngredientGroup::class, 'ingredient_group_ingredient');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
