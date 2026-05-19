<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\KitchenOrder;
use App\Models\PreparationStation;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class KitchenPanel extends Component
{
    /** The station the operator is currently watching. Null = all assigned. */
    public ?int $selectedStationId = null;

    public ?int $branchId = null;

    /** @var array<int> Station ids the current user is allowed to see. */
    public array $myStationIds = [];

    public int $lastKnownCount = 0;
    public bool $firstLoad = true;

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = $user->branch_id ?? Branch::where('is_active', true)->value('id');
        $this->myStationIds = $user->preparationStations()->pluck('preparation_stations.id')->map(fn($i) => (int) $i)->toArray();

        $this->lastKnownCount = $this->queryOrders()->where('status', 'pending')->count();
    }

    public function selectStation(?int $stationId): void
    {
        if ($stationId !== null && !in_array($stationId, $this->myStationIds, true)) {
            return; // ignore — not assigned to this user
        }
        $this->selectedStationId = $stationId;
    }

    public function poll(): void
    {
        if (empty($this->myStationIds)) {
            return;
        }

        $currentCount = $this->queryOrders()->where('status', 'pending')->count();

        if (!$this->firstLoad && $currentCount > $this->lastKnownCount) {
            $this->dispatch('play-kitchen-sound');
            $this->dispatch('notify', message: '¡Nueva comanda!', type: 'info');
        }

        $this->lastKnownCount = $currentCount;
        $this->firstLoad      = false;
    }

    public function takeOrder(int $orderId): void
    {
        $order = $this->findAuthorizedOrder($orderId);
        if (!$order) return;

        $order->update(['status' => 'preparing', 'taken_at' => now()]);
        $order->items()->update(['status' => 'preparing']);

        $this->dispatch('notify', message: 'Comanda tomada', type: 'success');
    }

    public function markReady(int $orderId): void
    {
        $order = $this->findAuthorizedOrder($orderId);
        if (!$order) return;

        $order->update(['status' => 'ready', 'ready_at' => now()]);
        $order->items()->update(['status' => 'ready']);

        $this->dispatch('notify', message: 'Comanda lista para entregar', type: 'success');
    }

    public function markDelivered(int $orderId): void
    {
        $order = $this->findAuthorizedOrder($orderId);
        if (!$order) return;

        $order->update(['status' => 'delivered', 'delivered_at' => now()]);
        $order->items()->update(['status' => 'delivered']);

        $this->dispatch('notify', message: 'Comanda entregada', type: 'success');
    }

    /**
     * Only return the order if it belongs to one of the stations assigned
     * to this user, so they cannot manipulate comandas outside their scope.
     */
    protected function findAuthorizedOrder(int $orderId): ?KitchenOrder
    {
        if (empty($this->myStationIds)) return null;

        return KitchenOrder::where('id', $orderId)
            ->where('branch_id', $this->branchId)
            ->whereIn('preparation_station_id', $this->myStationIds)
            ->first();
    }

    protected function queryOrders()
    {
        $query = KitchenOrder::query()
            ->where('branch_id', $this->branchId)
            ->whereIn('status', ['pending', 'preparing', 'ready']);

        if (empty($this->myStationIds)) {
            // User has no stations → show nothing.
            return $query->whereRaw('1 = 0');
        }

        if ($this->selectedStationId && in_array($this->selectedStationId, $this->myStationIds, true)) {
            $query->where('preparation_station_id', $this->selectedStationId);
        } else {
            $query->whereIn('preparation_station_id', $this->myStationIds);
        }

        return $query;
    }

    public function render()
    {
        $myStations = PreparationStation::whereIn('id', $this->myStationIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $orders = $this->queryOrders()
            ->with(['mesa.sector', 'preparationStation', 'items', 'user'])
            ->orderBy('sent_at')
            ->get()
            ->groupBy('status');

        return view('livewire.kitchen-panel', [
            'myStations' => $myStations,
            'pending'   => $orders->get('pending')   ?? collect(),
            'preparing' => $orders->get('preparing') ?? collect(),
            'ready'     => $orders->get('ready')     ?? collect(),
        ]);
    }
}
