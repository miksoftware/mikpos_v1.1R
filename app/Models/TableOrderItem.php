<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableOrderItem extends Model
{
    protected $fillable = [
        'table_order_id',
        'item_type',
        'item_id',
        'item_name',
        'unit_price',
        'quantity',
        'subtotal',
        'group_selections',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'quantity' => 'decimal:3',
            'subtotal' => 'decimal:2',
            'group_selections' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(TableOrder::class, 'table_order_id');
    }
}
