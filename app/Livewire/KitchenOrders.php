<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\KitchenOrder;
use App\Models\PreparationStation;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class KitchenOrders extends Component
{
    public ?int $selectedStationId = null;   // null = Todas las estaciones
    public ?int $branchId = null;
    public int $lastKnownCount = 0;          // To detect new orders for the sound
    public bool $firstLoad = true;

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = $user->branch_id;

        if (!$this->branchId) {
            $this->branchId = Branch::where('is_active', true)->value('id');
        }

        $this->lastKnownCount = $this->queryOrders()->count();
    }

    public function selectStation(?int $stationId): void
    {
        $this->selectedStationId = $stationId;
    }

    // Polling — detects new pending orders and emits an event so Alpine plays the sound.
    public function poll(): void
    {
        $currentCount = $this->queryOrders()
            ->where('status', 'pending')
            ->count();

        if (!$this->firstLoad && $currentCount > $this->lastKnownCount) {
            $this->dispatch('play-kitchen-sound');
            $this->dispatch('notify', message: 'Nueva comanda recibida', type: 'info');
        }

        $this->lastKnownCount = $currentCount;
        $this->firstLoad      = false;
    }

    public function takeOrder(int $orderId): void
    {
        if (!auth()->user()->hasPermission('kitchen.manage')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $order = KitchenOrder::find($orderId);
        if (!$order) return;

        $order->update([
            'status'   => 'preparing',
            'taken_at' => now(),
        ]);
        $order->items()->update(['status' => 'preparing']);

        $this->dispatch('notify', message: 'Comanda en preparación', type: 'success');
    }

    public function markReady(int $orderId): void
    {
        if (!auth()->user()->hasPermission('kitchen.manage')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $order = KitchenOrder::find($orderId);
        if (!$order) return;

        $order->update([
            'status'   => 'ready',
            'ready_at' => now(),
        ]);
        $order->items()->update(['status' => 'ready']);

        $this->dispatch('notify', message: 'Comanda lista', type: 'success');
    }

    public function markDelivered(int $orderId): void
    {
        if (!auth()->user()->hasPermission('kitchen.manage')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $order = KitchenOrder::find($orderId);
        if (!$order) return;

        $order->update([
            'status'       => 'delivered',
            'delivered_at' => now(),
        ]);
        $order->items()->update(['status' => 'delivered']);

        $this->dispatch('notify', message: 'Comanda entregada', type: 'success');
    }

    public function cancelOrder(int $orderId): void
    {
        if (!auth()->user()->hasPermission('kitchen.manage')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $order = \App\Models\KitchenOrder::with('items', 'cuenta')->find($orderId);
        if (!$order) return;

        \Illuminate\Support\Facades\DB::transaction(function () use ($order) {
            // Collect the cuenta_item_ids linked to this kitchen order
            $cuentaItemIds = $order->items()->pluck('cuenta_item_id')->filter()->all();

            // Mark the order + its items as cancelled
            $order->update(['status' => 'cancelled']);
            $order->items()->update(['status' => 'cancelled']);

            // Only delete cuenta_items if the cuenta is still open.
            // If the cuenta is closed/paid we don't rewrite history.
            $cuenta = $order->cuenta;
            if ($cuenta && $cuenta->status === 'abierta' && !empty($cuentaItemIds)) {
                \App\Models\CuentaItem::whereIn('id', $cuentaItemIds)->delete();

                // If the cuenta ends up empty, close it and free the mesa.
                $remaining = \App\Models\CuentaItem::where('cuenta_id', $cuenta->id)->count();
                if ($remaining === 0) {
                    $cuenta->update(['status' => 'cancelada']);
                    if ($cuenta->mesa_id) {
                        \App\Models\Mesa::where('id', $cuenta->mesa_id)->update(['status' => 'libre']);
                    }
                }
            }
        });

        $this->dispatch('notify', message: 'Comanda cancelada y removida de la mesa', type: 'success');
    }

    protected function queryOrders()
    {
        $q = KitchenOrder::query()
            ->where('branch_id', $this->branchId)
            ->whereIn('status', ['pending', 'preparing', 'ready']);

        if ($this->selectedStationId) {
            $q->where('preparation_station_id', $this->selectedStationId);
        }

        return $q;
    }

    public function render()
    {
        $stations = PreparationStation::where('is_active', true)->orderBy('name')->get();

        $orders = $this->queryOrders()
            ->with(['mesa.sector', 'preparationStation', 'items', 'user'])
            ->orderBy('sent_at')
            ->get()
            ->groupBy('status');

        return view('livewire.kitchen-orders', [
            'stations' => $stations,
            'pending'   => $orders->get('pending')   ?? collect(),
            'preparing' => $orders->get('preparing') ?? collect(),
            'ready'     => $orders->get('ready')     ?? collect(),
        ]);
    }
}
