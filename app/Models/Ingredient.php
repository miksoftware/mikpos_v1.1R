<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Ingredient extends Model
{
    protected $fillable = [
        'branch_id',
        'sku',
        'name',
        'description',
        'category_id',
        'unit_id',
        'tax_id',
        'cost',
        'sale_price',
        'price_includes_tax',
        'available_for_sale',
        'current_stock',
        'min_stock',
        'max_stock',
        'image',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'available_for_sale' => 'boolean',
            'price_includes_tax' => 'boolean',
            'cost' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'current_stock' => 'decimal:3',
            'min_stock' => 'decimal:3',
            'max_stock' => 'decimal:3',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function groupOptions(): HasMany
    {
        return $this->hasMany(IngredientGroupOption::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForBranch(Builder $query, ?int $branchId = null): Builder
    {
        return $branchId ? $query->where('branch_id', $branchId) : $query;
    }

    public function scopeAvailableForSale(Builder $query): Builder
    {
        return $query->where('available_for_sale', true);
    }

    public function isLowStock(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }

    public function generateSku(): string
    {
        $prefix = 'ING';
        $prefixLength = strlen($prefix) + 2;

        $last = static::where('sku', 'like', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTRING(sku, {$prefixLength}) AS UNSIGNED) DESC")
            ->first();

        $nextNumber = 1;
        if ($last && $last->sku) {
            $parts = explode('-', $last->sku);
            if (count($parts) === 2 && is_numeric($parts[1])) {
                $nextNumber = (int) $parts[1] + 1;
            }
        }

        $sku = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);

        while (static::where('sku', $sku)->exists()) {
            $nextNumber++;
            $sku = $prefix . '-' . str_pad($nextNumber, 5, '0', STR_PAD_LEFT);
        }

        $this->sku = $sku;
        return $sku;
    }

    public function getSalePriceWithoutTax(): float
    {
        if (!$this->price_includes_tax || !$this->tax) {
            return (float) $this->sale_price;
        }
        return $this->sale_price / (1 + ($this->tax->value / 100));
    }

    public function getSalePriceWithTax(): float
    {
        if ($this->price_includes_tax || !$this->tax) {
            return (float) $this->sale_price;
        }
        return $this->sale_price * (1 + ($this->tax->value / 100));
    }
}
