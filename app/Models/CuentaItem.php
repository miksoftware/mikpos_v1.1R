<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'quantity'   => 'decimal:3',
        'tax_rate'   => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'subtotal'   => 'decimal:2',
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
}
