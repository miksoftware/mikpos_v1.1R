<?php

namespace App\Livewire;

use App\Models\Ingredient;
use App\Models\IngredientGroup;
use App\Models\PreparationStation;
use App\Models\Tax;
use App\Models\Unit;
use App\Models\Category;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Ingredients extends Component
{
    use WithPagination;

    // Tab state
    public string $activeTab = 'ingredients';

    // ─── Ingredientes ──────────────────────────────────────────────────────────
    public string $search = '';
    public bool $isModalOpen = false;
    public bool $isDeleteModalOpen = false;
    public ?int $itemIdToDelete = null;

    public ?int $itemId = null;
    public string $name = '';
    public ?string $unit_id = null;
    public string $stock = '';
    public string $purchase_price = '';
    public string $sale_price = '';
    public bool $includes_tax = false;
    public ?string $tax_id = null;
    public bool $manage_inventory = false;
    public bool $show_in_pos = true;
    public bool $is_active = true;
    public ?int $preparationStationId = null;
    public ?int $category_id = null;

    // ─── Grupos ────────────────────────────────────────────────────────────────
    public string $searchGroup = '';
    public bool $isGroupModalOpen = false;
    public bool $isGroupDeleteModalOpen = false;
    public ?int $groupIdToDelete = null;

    public ?int $groupId = null;
    public string $groupName = '';
    public bool $groupIsActive = true;
    public array $selectedIngredients = [];

    // ─── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $ingredients = Ingredient::query()
            ->when(trim($this->search), fn($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
            ->with(['unit', 'tax'])
            ->withCount('groups')
            ->latest()
            ->paginate(10, pageName: 'ingredientsPage');

        $groups = IngredientGroup::query()
            ->when(trim($this->searchGroup), fn($q) => $q->where('name', 'like', '%' . trim($this->searchGroup) . '%'))
            ->withCount('ingredients')
            ->latest()
            ->paginate(10, pageName: 'groupsPage');

        $allIngredients = Ingredient::where('is_active', true)->orderBy('name')->get();
        $units = Unit::where('is_active', true)->orderBy('name')->get();
        $taxes = Tax::where('is_active', true)->orderBy('name')->get();

        return view('livewire.ingredients', [
            'ingredients'    => $ingredients,
            'groups'         => $groups,
            'allIngredients' => $allIngredients,
            'units'          => $units,
            'taxes'          => $taxes,
            'categories'     => Category::where('is_active', true)->orderBy('name')->get(),
            'preparationStations' => PreparationStation::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    // ─── Switch tab ────────────────────────────────────────────────────────────

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage('ingredientsPage');
        $this->resetPage('groupsPage');
    }

    // ─── CRUD Ingredientes ─────────────────────────────────────────────────────

    public function create(): void
    {
        if (!auth()->user()->hasPermission('ingredients.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear ingredientes', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit(int $id): void
    {
        if (!auth()->user()->hasPermission('ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar ingredientes', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Ingredient::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->unit_id = $item->unit_id ? (string) $item->unit_id : null;
        $this->stock = $item->stock !== null ? (string) $item->stock : '';
        $this->purchase_price = $item->purchase_price !== null ? (string) $item->purchase_price : '';
        $this->sale_price = $item->sale_price !== null ? (string) $item->sale_price : '';
        $this->includes_tax = $item->includes_tax;
        $this->tax_id = $item->tax_id ? (string) $item->tax_id : null;
        $this->manage_inventory = $item->manage_inventory;
        $this->show_in_pos = $item->show_in_pos;
        $this->is_active = $item->is_active;
        $this->preparationStationId = $item->preparation_station_id;
        $this->category_id = $item->category_id;
        $this->isModalOpen = true;
    }

    public function store(): void
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'ingredients.create' : 'ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate(
            [
                'name'           => 'required|min:2|unique:ingredients,name,' . $this->itemId,
                'unit_id'        => 'nullable|exists:units,id',
                'stock'          => 'nullable|numeric|min:0',
                'purchase_price' => 'nullable|numeric|min:0',
                'sale_price'     => 'nullable|numeric|min:0',
                'tax_id'         => 'nullable|exists:taxes,id',
                'category_id'    => 'nullable|exists:categories,id',
            ],
            [
                'name.required'           => 'El nombre del ingrediente es obligatorio.',
                'name.min'                => 'El nombre debe tener al menos 2 caracteres.',
                'name.unique'             => 'Ya existe un ingrediente con ese nombre.',
                'unit_id.exists'          => 'La unidad de medida seleccionada no es válida.',
                'tax_id.exists'           => 'El impuesto seleccionado no es válido.',
                'stock.numeric'           => 'El stock debe ser un número.',
                'stock.min'               => 'El stock no puede ser negativo.',
                'purchase_price.numeric'  => 'El precio de compra debe ser un número.',
                'purchase_price.min'      => 'El precio de compra no puede ser negativo.',
                'sale_price.numeric'      => 'El precio de venta debe ser un número.',
                'sale_price.min'          => 'El precio de venta no puede ser negativo.',
                'category_id.exists'      => 'La categoría seleccionada no es válida.',
            ]
        );

        $oldValues = $isNew ? null : Ingredient::find($this->itemId)?->toArray();
        $item = Ingredient::updateOrCreate(['id' => $this->itemId], [
            'name'             => $this->name,
            'unit_id'          => ($this->manage_inventory || !$this->show_in_pos) ? ($this->unit_id ?: null) : null,
            'stock'            => $this->manage_inventory && $this->stock !== '' ? (float) $this->stock : null,
            'purchase_price'   => $this->purchase_price !== '' ? (float) $this->purchase_price : null,
            'sale_price'       => $this->show_in_pos && $this->sale_price !== '' ? (float) $this->sale_price : null,
            'includes_tax'     => $this->show_in_pos ? $this->includes_tax : false,
            'tax_id'           => ($this->show_in_pos && $this->includes_tax) ? ($this->tax_id ?: null) : null,
            'manage_inventory' => $this->manage_inventory,
            'show_in_pos'      => $this->show_in_pos,
            'is_active'        => $this->is_active,
            'preparation_station_id' => $this->preparationStationId ?: null,
            'category_id'      => $this->show_in_pos ? ($this->category_id ?: null) : null,
        ]);

        if ($isNew) {
            ActivityLogService::logCreate('ingredients', $item, "Ingrediente '{$item->name}' creado");
        } else {
            ActivityLogService::logUpdate('ingredients', $item, $oldValues, "Ingrediente '{$item->name}' actualizado");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Ingrediente creado' : 'Ingrediente actualizado');
    }

    public function confirmDelete(int $id): void
    {
        if (!auth()->user()->hasPermission('ingredients.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar ingredientes', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->isDeleteModalOpen = true;
    }

    public function delete(): void
    {
        if (!auth()->user()->hasPermission('ingredients.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Ingredient::find($this->itemIdToDelete);
        if (!$item) {
            $this->isDeleteModalOpen = false;
            return;
        }
        if ($item->groups()->count() > 0) {
            $this->dispatch('notify', message: 'No se puede eliminar: el ingrediente pertenece a uno o más grupos. Desactívelo en su lugar.', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('ingredients', $item, "Ingrediente '{$item->name}' eliminado");
        $item->delete();
        $this->isDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Ingrediente eliminado');
    }

    public function toggleStatus(int $id): void
    {
        if (!auth()->user()->hasPermission('ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Ingredient::findOrFail($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('ingredients', $item, $oldValues, "Ingrediente '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Ingrediente activado' : 'Ingrediente desactivado');
    }

    // ─── CRUD Grupos ──────────────────────────────────────────────────────────

    public function createGroup(): void
    {
        if (!auth()->user()->hasPermission('ingredient_groups.create')) {
            $this->dispatch('notify', message: 'No tienes permiso para crear grupos', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetGroupForm();
        $this->isGroupModalOpen = true;
    }

    public function editGroup(int $id): void
    {
        if (!auth()->user()->hasPermission('ingredient_groups.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso para editar grupos', type: 'error');
            return;
        }
        $this->resetValidation();
        $group = IngredientGroup::with('ingredients')->findOrFail($id);
        $this->groupId = $group->id;
        $this->groupName = $group->name;
        $this->groupIsActive = $group->is_active;
        $this->selectedIngredients = $group->ingredients->pluck('id')->map(fn($id) => (string) $id)->toArray();
        $this->isGroupModalOpen = true;
    }

    public function storeGroup(): void
    {
        $isNew = !$this->groupId;
        if (!auth()->user()->hasPermission($isNew ? 'ingredient_groups.create' : 'ingredient_groups.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate(
            [
                'groupName' => 'required|min:2|unique:ingredient_groups,name,' . $this->groupId,
            ],
            [
                'groupName.required' => 'El nombre del grupo es obligatorio.',
                'groupName.min'      => 'El nombre debe tener al menos 2 caracteres.',
                'groupName.unique'   => 'Ya existe un grupo con ese nombre.',
            ]
        );

        $oldValues = $isNew ? null : IngredientGroup::find($this->groupId)?->toArray();
        $group = IngredientGroup::updateOrCreate(['id' => $this->groupId], [
            'name'      => $this->groupName,
            'is_active' => $this->groupIsActive,
        ]);

        $group->ingredients()->sync($this->selectedIngredients);

        if ($isNew) {
            ActivityLogService::logCreate('ingredient_groups', $group, "Grupo de ingredientes '{$group->name}' creado");
        } else {
            ActivityLogService::logUpdate('ingredient_groups', $group, $oldValues, "Grupo de ingredientes '{$group->name}' actualizado");
        }

        $this->isGroupModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Grupo creado' : 'Grupo actualizado');
    }

    public function confirmDeleteGroup(int $id): void
    {
        if (!auth()->user()->hasPermission('ingredient_groups.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso para eliminar grupos', type: 'error');
            return;
        }
        $this->groupIdToDelete = $id;
        $this->isGroupDeleteModalOpen = true;
    }

    public function deleteGroup(): void
    {
        if (!auth()->user()->hasPermission('ingredient_groups.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $group = IngredientGroup::find($this->groupIdToDelete);
        if (!$group) {
            $this->isGroupDeleteModalOpen = false;
            return;
        }
        ActivityLogService::logDelete('ingredient_groups', $group, "Grupo de ingredientes '{$group->name}' eliminado");
        $group->ingredients()->detach();
        $group->delete();
        $this->isGroupDeleteModalOpen = false;
        $this->dispatch('notify', message: 'Grupo eliminado');
    }

    public function toggleGroupStatus(int $id): void
    {
        if (!auth()->user()->hasPermission('ingredient_groups.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $group = IngredientGroup::findOrFail($id);
        $oldValues = $group->toArray();
        $group->is_active = !$group->is_active;
        $group->save();
        ActivityLogService::logUpdate('ingredient_groups', $group, $oldValues, "Grupo '{$group->name}' " . ($group->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $group->is_active ? 'Grupo activado' : 'Grupo desactivado');
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function resetForm(): void
    {
        $this->itemId = null;
        $this->name = '';
        $this->unit_id = null;
        $this->stock = '';
        $this->purchase_price = '';
        $this->sale_price = '';
        $this->includes_tax = false;
        $this->tax_id = null;
        $this->manage_inventory = false;
        $this->show_in_pos = true;
        $this->is_active = true;
        $this->preparationStationId = null;
        $this->category_id = null;
    }

    private function resetGroupForm(): void
    {
        $this->groupId = null;
        $this->groupName = '';
        $this->groupIsActive = true;
        $this->selectedIngredients = [];
    }
}
