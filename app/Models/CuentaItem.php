<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CuentaItem extends Model
{
    protected $table = 'cuenta_items';

    protected $fillable = [
        'cuenta_id',
        'product_id',
        'ingredient_id',
        'item_name',
        'unit_price',
        'quantity',
        'tax_rate',
        'tax_amount',
        'subtotal',
        'notes',
        'preparation_station_id',
        'sent_at',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity'   => 'decimal:3',
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal'   => 'decimal:2',
        'sent_at'    => 'datetime',
    ];

    // Relationships

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }

    public function preparationStation(): BelongsTo
    {
        return $this->belongsTo(PreparationStation::class);
    }

    public function selectedIngredients(): HasMany
    {
        return $this->hasMany(CuentaItemSelectedIngredient::class);
    }
}
