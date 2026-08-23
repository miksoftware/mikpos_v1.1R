<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitchenNotification extends Model
{
    protected $table = 'kitchen_notifications';

    protected $fillable = [
        'branch_id',
        'user_id',
        'kitchen_order_id',
        'cuenta_id',
        'mesa_id',
        'preparation_station_id',
        'station_name',
        'station_icon',
        'station_color',
        'mesa_name',
        'order_number',
        'status',
        'title',
        'message',
        'read',
        'read_at',
    ];

    protected $casts = [
        'read'    => 'boolean',
        'read_at' => 'datetime',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function kitchenOrder(): BelongsTo
    {
        return $this->belongsTo(KitchenOrder::class);
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

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Create a notification record when a kitchen order is marked ready or delivered.
     */
    public static function createForKitchenOrder(KitchenOrder $order, string $status = 'ready'): ?self
    {
        // Load relationships if not eager loaded
        $order->loadMissing(['items', 'preparationStation', 'mesa.sector', 'branch']);

        // Check if branch handles preparation stations / kitchen orders
        $branch = $order->branch ?? Branch::find($order->branch_id);
        if ($branch && !$branch->use_preparation_stations) {
            return null;
        }

        $stationName  = $order->preparationStation?->name ?? 'Cocina';
        $stationIcon  = $order->preparationStation?->icon ?? '🍳';
        $stationColor = $order->preparationStation?->color ?? '#6b7280';

        $mesaText = $order->mesa?->name ?? 'Sin mesa';
        if ($order->mesa?->sector) {
            $mesaText .= ' (' . $order->mesa->sector->name . ')';
        }

        // Build item summary (e.g. "2x Michelada Mango, 1x Papas Fritas")
        $itemParts = [];
        foreach ($order->items as $item) {
            $qty = rtrim(rtrim(number_format((float)$item->quantity, 3), '0'), '.');
            $itemParts[] = "{$qty}x {$item->item_name}";
        }
        $message = !empty($itemParts) ? implode(', ', $itemParts) : 'Comanda lista para entrega';

        if ($status === 'delivered') {
            $title = "✅ {$stationIcon} {$stationName} entregado · {$mesaText}";
        } else {
            $title = "🍽️ {$stationIcon} ¡{$stationName} listo! · {$mesaText}";
        }

        return static::create([
            'branch_id'              => $order->branch_id,
            'user_id'                => $order->user_id,
            'kitchen_order_id'       => $order->id,
            'cuenta_id'              => $order->cuenta_id,
            'mesa_id'                => $order->mesa_id,
            'preparation_station_id' => $order->preparation_station_id,
            'station_name'           => $stationName,
            'station_icon'           => $stationIcon,
            'station_color'          => $stationColor,
            'mesa_name'              => $mesaText,
            'order_number'           => $order->number,
            'status'                 => $status,
            'title'                  => $title,
            'message'                => $message,
            'read'                   => false,
        ]);
    }
}
