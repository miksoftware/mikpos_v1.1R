<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Cuenta extends Model
{
    protected $table = 'cuentas';

    protected $fillable = [
        'mesa_id',
        'user_id',
        'branch_id',
        'num_persons',
        'status',
        'notes',
        'sale_id',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Relationships

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CuentaItem::class);
    }

    // Scopes

    public function scopeAbierta(Builder $query): Builder
    {
        return $query->where('status', 'abierta');
    }

    // Computed

    public function getSubtotal(): float
    {
        return (float) $this->items->sum('subtotal');
    }

    public function getTaxTotal(): float
    {
        return (float) $this->items->sum('tax_amount');
    }

    public function getTotal(): float
    {
        return $this->getSubtotal() + $this->getTaxTotal();
    }

    public function getItemCount(): float
    {
        return (float) $this->items->sum('quantity');
    }
}
