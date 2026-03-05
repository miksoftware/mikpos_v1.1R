<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IngredientGroupOption extends Model
{
    protected $fillable = [
        'ingredient_group_id',
        'ingredient_id',
        'quantity',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(IngredientGroup::class, 'ingredient_group_id');
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
