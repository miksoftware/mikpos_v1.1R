<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Zone;
use App\Models\RestaurantTable;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ZonesAndTables extends Component
{
    use WithPagination;

    // Tab control
    public string $activeTab = 'zones';

    // Search & filters
    public string $search = '';
    public string $filterZone = '';

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];
    public ?string $filterBranch = null;

    // Zone form
    public bool $isZoneModalOpen = false;
    public bool $isZoneDeleteModalOpen = false;
    public $zoneIdToDelete = null;
    public $zoneId;
    public string $zoneName = '';
    public string $zoneDescription = '';
    public ?string $zoneColor = '#6366f1';
    public bool $zoneIsActive = true;

    // Table form
    public bool $isTableModalOpen = false;
    public bool $isTableDeleteModalOpen = false;
    public $tableIdToDelete = null;
    public $tableId;
    public $tableZoneId = '';
    public string $tableName = '';
    public int $tableCapacity = 4;
    public bool $tableIsActive = true;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterZone()
    {
        $this->resetPage();
    }

    public function updatedFilterBranch()
    {
        $this->resetPage();
    }

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;

        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function switchTab(string $tab)
    {
        $this->activeTab = $tab;
        $this->search = '';
        $this->filterZone = '';
        $this->resetPage();
    }

    private function getBranchId(): ?int
    {
        $user = auth()->user();
        if ($this->needsBranchSelection) {
            return $this->filterBranch ? (int) $this->filterBranch : null;
        }
        return $user->branch_id;
    }

    // ─── ZONES ───

    public function createZone()
    {
        if (!auth()->user()->hasPermission('zones_tables.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetZoneForm();
        $this->isZoneModalOpen = true;
    }

    public function editZone($id)
    {
        if (!auth()->user()->hasPermission('zones_tables.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $zone = Zone::findOrFail($id);
        $this->zoneId = $zone->id;
        $this->zoneName = $zone->name;
        $this->zoneDescription = $zone->description ?? '';
        $this->zoneColor = $zone->color ?? '#6366f1';
        $this->zoneIsActive = $zone->is_active;
        $this->isZoneModalOpen = true;
    }

    public function storeZone()
    {
        $isNew = !$this->zoneId;
        if (!auth()->user()->hasPermission($isNew ? 'zones_tables.create' : 'zones_tables.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $branchId = $this->getBranchId();
        if (!$branchId && !$this->zoneId) {
            $this->dispatch('notify', message: 'Debes seleccionar una sucursal primero', type: 'error');
            return;
        }

        $this->validate([
            'zoneName' => 'required|min:2',
            'zoneColor' => 'nullable|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        $oldValues = $isNew ? null : Zone::find($this->zoneId)->toArray();

        $data = [
            'name' => $this->zoneName,
            'description' => $this->zoneDescription ?: null,
            'color' => $this->zoneColor,
            'is_active' => $this->zoneIsActive,
        ];

        if ($isNew) {
            $data['branch_id'] = $branchId;
        }

        $zone = Zone::updateOrCreate(['id' => $this->zoneId], $data);

        $isNew
            ? ActivityLogService::logCreate('zones_tables', $zone, "Zona '{$zone->name}' creada")
            : ActivityLogService::logUpdate('zones_tables', $zone, $oldValues, "Zona '{$zone->name}' actualizada");

        $this->isZoneModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Zona creada' : 'Zona actualizada');
    }

    public function confirmDeleteZone($id)
    {
        if (!auth()->user()->hasPermission('zones_tables.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->zoneIdToDelete = $id;
        $this->isZoneDeleteModalOpen = true;
    }

    public function deleteZone()
    {
        if (!auth()->user()->hasPermission('zones_tables.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $zone = Zone::find($this->zoneIdToDelete);
        if ($zone->tables()->count() > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar: tiene mesas asociadas. Elimine las mesas primero.', type: 'error');
            $this->isZoneDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('zones_tables', $zone, "Zona '{$zone->name}' eliminada");
        $zone->delete();
        $this->isZoneDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Zona eliminada');
    }

    public function toggleZoneStatus($id)
    {
        if (!auth()->user()->hasPermission('zones_tables.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $zone = Zone::find($id);
        $oldValues = $zone->toArray();
        $zone->is_active = !$zone->is_active;
        $zone->save();
        ActivityLogService::logUpdate('zones_tables', $zone, $oldValues, "Zona '{$zone->name}' " . ($zone->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $zone->is_active ? 'Activada' : 'Desactivada');
    }

    private function resetZoneForm()
    {
        $this->zoneId = null;
        $this->zoneName = '';
        $this->zoneDescription = '';
        $this->zoneColor = '#6366f1';
        $this->zoneIsActive = true;
    }

    // ─── TABLES ───

    public function createTable()
    {
        if (!auth()->user()->hasPermission('zones_tables.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetTableForm();
        $this->isTableModalOpen = true;
    }

    public function editTable($id)
    {
        if (!auth()->user()->hasPermission('zones_tables.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $table = RestaurantTable::findOrFail($id);
        $this->tableId = $table->id;
        $this->tableZoneId = $table->zone_id;
        $this->tableName = $table->name;
        $this->tableCapacity = $table->capacity;
        $this->tableIsActive = $table->is_active;
        $this->isTableModalOpen = true;
    }

    public function storeTable()
    {
        $isNew = !$this->tableId;
        if (!auth()->user()->hasPermission($isNew ? 'zones_tables.create' : 'zones_tables.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'tableZoneId' => 'required|exists:zones,id',
            'tableName' => 'required|min:1',
            'tableCapacity' => 'required|integer|min:1|max:50',
        ]);

        $oldValues = $isNew ? null : RestaurantTable::find($this->tableId)->toArray();

        $table = RestaurantTable::updateOrCreate(['id' => $this->tableId], [
            'zone_id' => $this->tableZoneId,
            'name' => $this->tableName,
            'capacity' => $this->tableCapacity,
            'is_active' => $this->tableIsActive,
        ]);

        $isNew
            ? ActivityLogService::logCreate('zones_tables', $table, "Mesa '{$table->name}' creada")
            : ActivityLogService::logUpdate('zones_tables', $table, $oldValues, "Mesa '{$table->name}' actualizada");

        $this->isTableModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Mesa creada' : 'Mesa actualizada');
    }

    public function confirmDeleteTable($id)
    {
        if (!auth()->user()->hasPermission('zones_tables.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->tableIdToDelete = $id;
        $this->isTableDeleteModalOpen = true;
    }

    public function deleteTable()
    {
        if (!auth()->user()->hasPermission('zones_tables.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $table = RestaurantTable::find($this->tableIdToDelete);
        ActivityLogService::logDelete('zones_tables', $table, "Mesa '{$table->name}' eliminada");
        $table->delete();
        $this->isTableDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Mesa eliminada');
    }

    public function toggleTableStatus($id)
    {
        if (!auth()->user()->hasPermission('zones_tables.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $table = RestaurantTable::find($id);
        $oldValues = $table->toArray();
        $table->is_active = !$table->is_active;
        $table->save();
        ActivityLogService::logUpdate('zones_tables', $table, $oldValues, "Mesa '{$table->name}' " . ($table->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $table->is_active ? 'Activada' : 'Desactivada');
    }

    private function resetTableForm()
    {
        $this->tableId = null;
        $this->tableZoneId = '';
        $this->tableName = '';
        $this->tableCapacity = 4;
        $this->tableIsActive = true;
    }

    // ─── RENDER ───

    public function render()
    {
        $branchId = $this->getBranchId();

        $zones = Zone::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->withCount('tables')
            ->when(trim($this->search) && $this->activeTab === 'zones', fn($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
            ->latest()
            ->paginate(10, ['*'], 'zonesPage');

        $tables = RestaurantTable::query()
            ->with('zone')
            ->whereHas('zone', fn($q) => $q->when($branchId, fn($q2) => $q2->where('branch_id', $branchId)))
            ->when(trim($this->search) && $this->activeTab === 'tables', fn($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
            ->when($this->filterZone, fn($q) => $q->where('zone_id', $this->filterZone))
            ->latest()
            ->paginate(10, ['*'], 'tablesPage');

        $allZones = Zone::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('livewire.zones-and-tables', [
            'zones' => $zones,
            'tables' => $tables,
            'allZones' => $allZones,
        ]);
    }
}
