<?php

namespace App\Livewire;

use App\Models\PreparationStation;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PreparationStations extends Component
{
    use WithPagination;

    public string $search = '';
    public bool $isModalOpen = false;
    public bool $isDeleteModalOpen = false;
    public ?int $itemIdToDelete = null;

    public ?int $itemId = null;
    public string $name = '';
    public string $icon = '';
    public string $color = '#6b7280';
    public string $description = '';
    public bool $is_active = true;

    public function render()
    {
        $items = PreparationStation::query()
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(15);

        return view('livewire.preparation-stations', ['items' => $items]);
    }

    public function create(): void
    {
        if (!auth()->user()->hasPermission('preparation_stations.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->hasPermission('preparation_stations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = PreparationStation::findOrFail($id);
        $this->itemId       = $item->id;
        $this->name         = $item->name;
        $this->icon         = $item->icon ?? '';
        $this->color        = $item->color ?? '#6b7280';
        $this->description  = $item->description ?? '';
        $this->is_active    = $item->is_active;
        $this->isModalOpen  = true;
    }

    public function store(): void
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'preparation_stations.create' : 'preparation_stations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name'  => 'required|min:2|max:60|unique:preparation_stations,name,' . $this->itemId,
            'icon'  => 'nullable|max:10',
            'color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{3,6}$/'],
        ]);

        $oldValues = $isNew ? null : PreparationStation::find($this->itemId)?->toArray();
        $item = PreparationStation::updateOrCreate(['id' => $this->itemId], [
            'name'        => $this->name,
            'icon'        => $this->icon ?: null,
            'color'       => $this->color ?: null,
            'description' => $this->description ?: null,
            'is_active'   => $this->is_active,
        ]);

        $isNew
            ? ActivityLogService::logCreate('preparation_stations', $item, "Estación '{$item->name}' creada")
            : ActivityLogService::logUpdate('preparation_stations', $item, $oldValues, "Estación '{$item->name}' actualizada");

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Estación creada' : 'Estación actualizada');
    }

    public function confirmDelete(int $id): void
    {
        if (!auth()->user()->hasPermission('preparation_stations.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if (!auth()->user()->hasPermission('preparation_stations.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = PreparationStation::find($this->itemIdToDelete);
        if ($item) {
            ActivityLogService::logDelete('preparation_stations', $item, "Estación '{$item->name}' eliminada");
            $item->delete();
        }
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Estación eliminada');
    }

    public function toggleStatus(int $id): void
    {
        if (!auth()->user()->hasPermission('preparation_stations.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = PreparationStation::findOrFail($id);
        $item->update(['is_active' => !$item->is_active]);
        $this->dispatch('notify', message: 'Estado actualizado');
    }

    public function resetForm(): void
    {
        $this->itemId      = null;
        $this->name        = '';
        $this->icon        = '';
        $this->color       = '#6b7280';
        $this->description = '';
        $this->is_active   = true;
    }
}
