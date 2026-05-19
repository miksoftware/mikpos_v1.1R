<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KitchenOrder extends Model
{
    protected $table = 'kitchen_orders';

    protected $fillable = [
        'number',
        'branch_id',
        'cuenta_id',
        'mesa_id',
        'preparation_station_id',
        'user_id',
        'status',
        'items_count',
        'notes',
        'sent_at',
        'taken_at',
        'ready_at',
        'delivered_at',
    ];

    protected $casts = [
        'sent_at'      => 'datetime',
        'taken_at'     => 'datetime',
        'ready_at'     => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public const STATUSES = ['pending', 'preparing', 'ready', 'delivered', 'cancelled'];

    // Relationships

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function cuenta(): BelongsTo
    {
        return $this->belongsTo(Cuenta::class);
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function preparationStation(): BelongsTo
    {
        return $this->belongsTo(PreparationStation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(KitchenOrderItem::class);
    }

    // Helpers

    public static function generateNumber(): string
    {
        $prefix = 'KO-' . now()->format('Ymd');
        $last = static::where('number', 'like', $prefix . '-%')
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($last && preg_match('/-(\d+)$/', $last->number, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'pending'   => 'Pendiente',
            'preparing' => 'En preparación',
            'ready'     => 'Listo',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default     => $this->status,
        };
    }
}
