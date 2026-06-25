<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CuentaItemSelectedIngredient extends Model
{
    protected $table = 'cuenta_item_selected_ingredients';

    protected $fillable = [
        'cuenta_item_id',
        'ingredient_group_id',
        'ingredient_id',
    ];

    public function cuentaItem(): BelongsTo
    {
        return $this->belongsTo(CuentaItem::class);
    }

    public function ingredientGroup(): BelongsTo
    {
        return $this->belongsTo(IngredientGroup::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
