<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenOrderItem extends Model
{
    protected $table = 'kitchen_order_items';

    protected $fillable = [
        'kitchen_order_id',
        'cuenta_item_id',
        'product_id',
        'ingredient_id',
        'item_name',
        'quantity',
        'notes',
        'status',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(KitchenOrder::class, 'kitchen_order_id');
    }

    public function cuentaItem(): BelongsTo
    {
        return $this->belongsTo(CuentaItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}
