<?php

namespace App\Livewire;

use App\Models\Mesa;
use App\Models\Sector;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Mesas extends Component
{
    use WithPagination;

    // Tab state
    public string $activeTab = 'sectores';

    // ─── Sectores ─────────────────────────────────────────────────────────────
    public string $searchSector = '';
    public bool $isSectorModalOpen = false;
    public bool $isSectorDeleteModalOpen = false;
    public bool $isSectorDeactivateModalOpen = false;
    public ?int $sectorIdToDelete = null;
    public ?int $sectorIdToDeactivate = null;
    public string $sectorNameToDeactivate = '';

    public ?int $sectorId = null;
    public string $sectorName = '';
    public bool $sectorIsActive = true;

    // ─── Mesas ────────────────────────────────────────────────────────────────
    public string $searchMesa = '';
    public ?int $filterSector = null;
    public bool $isMesaModalOpen = false;
    public bool $isMesaDeleteModalOpen = false;
    public ?int $mesaIdToDelete = null;

    public ?int $mesaId = null;
    public string $mesaName = '';
    public ?int $mesaSectorId = null;
    public bool $mesaIsActive = true;
    public int $mesaCapacity = 4;

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $sectores = Sector::query()
            ->when(trim($this->searchSector), fn($q) => $q->where('name', 'like', '%' . trim($this->searchSector) . '%'))
            ->withCount('mesas')
            ->withCount(['mesas as mesas_ocupadas_count' => fn($q) => $q->where('status', 'ocupada')])
            ->latest()
            ->paginate(10, pageName: 'sectoresPage');

        $mesas = Mesa::query()
            ->when(trim($this->searchMesa), fn($q) => $q->where('name', 'like', '%' . trim($this->searchMesa) . '%'))
            ->when($this->filterSector, fn($q) => $q->where('sector_id', $this->filterSector))
            ->with('sector')
            ->latest()
            ->paginate(10, pageName: 'mesasPage');

        $allSectores = Sector::where('is_active', true)->orderBy('name')->get();

        return view('livewire.mesas', [
            'sectores'    => $sectores,
            'mesas'       => $mesas,
            'allSectores' => $allSectores,
        ]);
    }

    // ─── Switch tab ───────────────────────────────────────────────────────────

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage('sectoresPage');
        $this->resetPage('mesasPage');
    }

    // ─── CRUD Sectores ────────────────────────────────────────────────────────

    public function createSector(): void
    {
        if (!auth()->user()->hasPermission('sectors.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear sectores', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetSectorForm();
        $this->isSectorModalOpen = true;
    }

    public function editSector(int $id): void
    {
        if (!auth()->user()->hasPermission('sectors.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar sectores', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Sector::findOrFail($id);
        $this->sectorId = $item->id;
        $this->sectorName = $item->name;
        $this->sectorIsActive = $item->is_active;
        $this->isSectorModalOpen = true;
    }

    public function storeSector(): void
    {
        $isNew = !$this->sectorId;
        if (!auth()->user()->hasPermission($isNew ? 'sectors.create' : 'sectors.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate(
            [
                'sectorName' => 'required|min:2|unique:sectores,name,' . $this->sectorId,
            ],
            [
                'sectorName.required' => 'El nombre del sector es obligatorio.',
                'sectorName.min'      => 'El nombre debe tener al menos 2 caracteres.',
                'sectorName.unique'   => 'Ya existe un sector con ese nombre.',
            ]
        );

        $oldValues = $isNew ? null : Sector::find($this->sectorId)?->toArray();
        $item = Sector::updateOrCreate(['id' => $this->sectorId], [
            'name'      => mb_strtoupper($this->sectorName),
            'is_active' => $this->sectorIsActive,
        ]);

        $isNew
            ? ActivityLogService::logCreate('sectors', $item, "Sector '{$item->name}' creado")
            : ActivityLogService::logUpdate('sectors', $item, $oldValues, "Sector '{$item->name}' actualizado");

        $this->isSectorModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Sector creado' : 'Sector actualizado');
    }

    public function toggleSectorStatus(int $id): void
    {
        if (!auth()->user()->hasPermission('sectors.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Sector::findOrFail($id);

        if ($item->is_active) {
            // About to deactivate — show warning modal
            $this->sectorIdToDeactivate = $id;
            $this->sectorNameToDeactivate = $item->name;
            $this->isSectorDeactivateModalOpen = true;
        } else {
            // Activate directly
            $oldValues = $item->toArray();
            $item->is_active = true;
            $item->save();
            ActivityLogService::logUpdate('sectors', $item, $oldValues, "Sector '{$item->name}' activado");
            $this->dispatch('notify', message: 'Sector activado');
        }
    }

    public function confirmDeactivateSector(): void
    {
        if (!auth()->user()->hasPermission('sectors.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = Sector::findOrFail($this->sectorIdToDeactivate);
        $oldValues = $item->toArray();

        // Deactivate all FREE mesas in this sector (skip occupied ones)
        Mesa::where('sector_id', $item->id)
            ->where('status', 'libre')
            ->update(['is_active' => false]);

        $item->is_active = false;
        $item->save();

        ActivityLogService::logUpdate('sectors', $item, $oldValues, "Sector '{$item->name}' desactivado (mesas libres desactivadas)");

        $this->isSectorDeactivateModalOpen = false;
        $this->sectorIdToDeactivate = null;
        $this->sectorNameToDeactivate = '';
        $this->dispatch('notify', message: 'Sector desactivado');
    }

    public function confirmDeleteSector(int $id): void
    {
        if (!auth()->user()->hasPermission('sectors.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar sectores', type: 'error');
            return;
        }
        $this->sectorIdToDelete = $id;
        $this->isSectorDeleteModalOpen = true;
    }

    public function deleteSector(): void
    {
        if (!auth()->user()->hasPermission('sectors.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = Sector::find($this->sectorIdToDelete);
        if (!$item) {
            $this->isSectorDeleteModalOpen = false;
            return;
        }

        if (!$item->canDelete()) {
            $this->dispatch('notify', message: 'No se puede eliminar: el sector tiene mesas asignadas', type: 'error');
            $this->isSectorDeleteModalOpen = false;
            return;
        }

        ActivityLogService::logDelete('sectors', $item, "Sector '{$item->name}' eliminado");
        $item->delete();

        $this->isSectorDeleteModalOpen = false;
        $this->sectorIdToDelete = null;
        $this->dispatch('notify', message: 'Sector eliminado');
    }

    // ─── CRUD Mesas ───────────────────────────────────────────────────────────

    public function createMesa(): void
    {
        if (!auth()->user()->hasPermission('mesas.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear mesas', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetMesaForm();
        $this->isMesaModalOpen = true;
    }

    public function editMesa(int $id): void
    {
        if (!auth()->user()->hasPermission('mesas.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar mesas', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Mesa::findOrFail($id);
        $this->mesaId = $item->id;
        $this->mesaName = $item->name;
        $this->mesaSectorId = $item->sector_id;
        $this->mesaIsActive = $item->is_active;
        $this->mesaCapacity = $item->capacity ?? 4;
        $this->isMesaModalOpen = true;
    }

    public function storeMesa(): void
    {
        $isNew = !$this->mesaId;
        if (!auth()->user()->hasPermission($isNew ? 'mesas.create' : 'mesas.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate(
            [
                'mesaName'     => 'required|min:2',
                'mesaSectorId' => 'required|exists:sectores,id',
            ],
            [
                'mesaName.required'     => 'El nombre de la mesa es obligatorio.',
                'mesaName.min'          => 'El nombre debe tener al menos 2 caracteres.',
                'mesaSectorId.required' => 'Debe seleccionar un sector.',
                'mesaSectorId.exists'   => 'El sector seleccionado no es válido.',
            ]
        );

        $oldValues = $isNew ? null : Mesa::find($this->mesaId)?->toArray();
        $item = Mesa::updateOrCreate(['id' => $this->mesaId], [
            'sector_id' => $this->mesaSectorId,
            'name'      => mb_strtoupper($this->mesaName),
            'capacity'  => $this->mesaCapacity,
            'is_active' => $this->mesaIsActive,
        ]);

        $isNew
            ? ActivityLogService::logCreate('mesas', $item, "Mesa '{$item->name}' creada")
            : ActivityLogService::logUpdate('mesas', $item, $oldValues, "Mesa '{$item->name}' actualizada");

        $this->isMesaModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Mesa creada' : 'Mesa actualizada');
    }

    public function toggleMesaStatus(int $id): void
    {
        if (!auth()->user()->hasPermission('mesas.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = Mesa::findOrFail($id);

        if ($item->is_active && $item->isOcupada()) {
            $this->dispatch('notify', message: 'No se puede desactivar: la mesa tiene una cuenta abierta', type: 'error');
            return;
        }

        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();

        ActivityLogService::logUpdate('mesas', $item, $oldValues, "Mesa '{$item->name}' " . ($item->is_active ? 'activada' : 'desactivada'));
        $this->dispatch('notify', message: $item->is_active ? 'Mesa activada' : 'Mesa desactivada');
    }

    public function confirmDeleteMesa(int $id): void
    {
        if (!auth()->user()->hasPermission('mesas.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar mesas', type: 'error');
            return;
        }
        $this->mesaIdToDelete = $id;
        $this->isMesaDeleteModalOpen = true;
    }

    public function deleteMesa(): void
    {
        if (!auth()->user()->hasPermission('mesas.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $item = Mesa::find($this->mesaIdToDelete);
        if (!$item) {
            $this->isMesaDeleteModalOpen = false;
            return;
        }

        if (!$item->canDelete()) {
            $this->dispatch('notify', message: 'No se puede eliminar: la mesa está ocupada', type: 'error');
            $this->isMesaDeleteModalOpen = false;
            return;
        }

        ActivityLogService::logDelete('mesas', $item, "Mesa '{$item->name}' eliminada");
        $item->delete();

        $this->isMesaDeleteModalOpen = false;
        $this->mesaIdToDelete = null;
        $this->dispatch('notify', message: 'Mesa eliminada');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resetSectorForm(): void
    {
        $this->sectorId = null;
        $this->sectorName = '';
        $this->sectorIsActive = true;
    }

    private function resetMesaForm(): void
    {
        $this->mesaId = null;
        $this->mesaName = '';
        $this->mesaSectorId = null;
        $this->mesaIsActive = true;
        $this->mesaCapacity = 4;
    }
}
