<?php

namespace App\Livewire;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\IngredientGroup;
use App\Models\IngredientGroupOption;
use App\Models\Tax;
use App\Models\Unit;
use App\Services\ActivityLogService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Ingredients extends Component
{
    use WithPagination;

    // Tab control
    public $activeTab = 'ingredients'; // ingredients | groups

    // Search & filters
    public $search = '';
    public $filterCategory = '';
    public $filterStatus = '';

    // Ingredient modal
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $itemIdToDelete = null;
    public $deleteType = 'ingredient'; // ingredient | group

    public $itemId;
    public $name;
    public $description;
    public $category_id;
    public $unit_id;
    public $tax_id;
    public $cost = 0;
    public $sale_price = 0;
    public $price_includes_tax = false;
    public $available_for_sale = false;
    public $current_stock = 0;
    public $min_stock = 0;
    public $max_stock = 0;
    public $is_active = true;

    // Group modal
    public $isGroupModalOpen = false;
    public $groupId;
    public $groupName;
    public $groupDescription;
    public $groupIsActive = true;
    public $groupOptions = [];

    // Group detail modal
    public $isGroupDetailOpen = false;
    public $viewingGroup = null;

    // Branch selection for super_admin
    public bool $needsBranchSelection = false;
    public $branches = [];
    public $branch_id = null;

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        if ($this->needsBranchSelection) {
            $this->branches = \App\Models\Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterCategory()
    {
        $this->resetPage();
    }

    public function updatingFilterStatus()
    {
        $this->resetPage();
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
        $this->search = '';
    }

    public function getBranchId()
    {
        if ($this->needsBranchSelection) {
            return $this->branch_id;
        }
        return auth()->user()->branch_id;
    }

    public function render()
    {
        $branchId = $this->getBranchId();

        if ($this->activeTab === 'ingredients') {
            $items = Ingredient::query()
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when(trim($this->search), fn($q) => $q->where(function ($q2) {
                    $q2->where('name', 'like', '%' . trim($this->search) . '%')
                       ->orWhere('sku', 'like', '%' . trim($this->search) . '%');
                }))
                ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
                ->when($this->filterStatus !== '', function ($q) {
                    if ($this->filterStatus === '1') $q->where('is_active', true);
                    if ($this->filterStatus === '0') $q->where('is_active', false);
                    if ($this->filterStatus === 'sale') $q->where('available_for_sale', true);
                })
                ->with(['category', 'unit', 'tax'])
                ->latest()
                ->paginate(10);
        } else {
            $items = IngredientGroup::query()
                ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
                ->when(trim($this->search), fn($q) => $q->where('name', 'like', '%' . trim($this->search) . '%'))
                ->withCount('options')
                ->latest()
                ->paginate(10);
        }

        return view('livewire.ingredients', [
            'items' => $items,
            'categories' => Category::where('is_active', true)->orderBy('name')->get(),
            'units' => Unit::where('is_active', true)->orderBy('name')->get(),
            'taxes' => Tax::orderBy('name')->get(),
            'branchIngredients' => $this->activeTab === 'groups'
                ? Ingredient::where('is_active', true)->when($branchId, fn($q) => $q->where('branch_id', $branchId))->orderBy('name')->get()
                : collect(),
        ]);
    }

    // ─── Ingredient CRUD ───

    public function create()
    {
        if (!auth()->user()->hasPermission('ingredients.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        $this->isModalOpen = true;
    }

    public function edit($id)
    {
        if (!auth()->user()->hasPermission('ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $item = Ingredient::findOrFail($id);
        $this->itemId = $item->id;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->category_id = $item->category_id;
        $this->unit_id = $item->unit_id;
        $this->tax_id = $item->tax_id;
        $this->cost = $item->cost;
        $this->sale_price = $item->sale_price;
        $this->price_includes_tax = $item->price_includes_tax;
        $this->available_for_sale = $item->available_for_sale;
        $this->current_stock = $item->current_stock;
        $this->min_stock = $item->min_stock;
        $this->max_stock = $item->max_stock;
        $this->is_active = $item->is_active;
        $this->isModalOpen = true;
    }

    public function store()
    {
        $isNew = !$this->itemId;
        if (!auth()->user()->hasPermission($isNew ? 'ingredients.create' : 'ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'name' => 'required|min:2',
            'unit_id' => 'required|exists:units,id',
            'cost' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'current_stock' => 'nullable|numeric|min:0',
        ] + ($this->needsBranchSelection ? ['branch_id' => 'required|exists:branches,id'] : []));

        $branchId = $this->getBranchId();
        if (!$branchId) {
            $this->dispatch('notify', message: 'Debes seleccionar una sucursal', type: 'error');
            return;
        }

        $oldValues = $isNew ? null : Ingredient::find($this->itemId)?->toArray();

        $data = [
            'branch_id' => $branchId,
            'name' => $this->name,
            'description' => $this->description,
            'category_id' => $this->category_id ?: null,
            'unit_id' => $this->unit_id,
            'tax_id' => $this->tax_id ?: null,
            'cost' => $this->cost ?? 0,
            'sale_price' => $this->sale_price ?? 0,
            'price_includes_tax' => $this->price_includes_tax,
            'available_for_sale' => $this->available_for_sale,
            'current_stock' => $this->current_stock ?? 0,
            'min_stock' => $this->min_stock ?? 0,
            'max_stock' => $this->max_stock ?? 0,
            'is_active' => $this->is_active,
        ];

        // Generate SKU before insert for new ingredients
        if ($isNew) {
            $temp = new Ingredient();
            $temp->generateSku();
            $data['sku'] = $temp->sku;
        }

        $item = Ingredient::updateOrCreate(['id' => $this->itemId], $data);

        if ($isNew) {
            ActivityLogService::logCreate('ingredients', $item, "Ingrediente '{$item->name}' creado");
        } else {
            ActivityLogService::logUpdate('ingredients', $item, $oldValues, "Ingrediente '{$item->name}' actualizado");
        }

        $this->isModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Ingrediente creado' : 'Ingrediente actualizado');
    }

    public function toggleStatus($id)
    {
        if (!auth()->user()->hasPermission('ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $item = Ingredient::find($id);
        $oldValues = $item->toArray();
        $item->is_active = !$item->is_active;
        $item->save();
        ActivityLogService::logUpdate('ingredients', $item, $oldValues, "Ingrediente '{$item->name}' " . ($item->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $item->is_active ? 'Activado' : 'Desactivado');
    }

    public function confirmDelete($id, $type = 'ingredient')
    {
        if (!auth()->user()->hasPermission('ingredients.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->itemIdToDelete = $id;
        $this->deleteType = $type;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('ingredients.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        if ($this->deleteType === 'group') {
            $group = IngredientGroup::find($this->itemIdToDelete);
            if ($group) {
                ActivityLogService::logDelete('ingredients', $group, "Grupo '{$group->name}' eliminado");
                $group->delete();
                $this->dispatch('notify', message: 'Grupo eliminado');
            }
        } else {
            $item = Ingredient::find($this->itemIdToDelete);
            if ($item) {
                if ($item->groupOptions()->count() > 0) {
                    $this->dispatch('notify', message: 'No se puede eliminar: está asignado a grupos. Desactívelo en su lugar.', type: 'error');
                    $this->isDeleteModalOpen = false;
                    return;
                }
                ActivityLogService::logDelete('ingredients', $item, "Ingrediente '{$item->name}' eliminado");
                $item->delete();
                $this->dispatch('notify', message: 'Ingrediente eliminado');
            }
        }

        $this->isDeleteModalOpen = false;
    }

    // ─── Group CRUD ───

    public function createGroup()
    {
        if (!auth()->user()->hasPermission('ingredients.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetGroupForm();
        $this->isGroupModalOpen = true;
    }

    public function editGroup($id)
    {
        if (!auth()->user()->hasPermission('ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $group = IngredientGroup::with('options')->findOrFail($id);
        $this->groupId = $group->id;
        $this->groupName = $group->name;
        $this->groupDescription = $group->description;
        $this->groupIsActive = $group->is_active;
        $this->groupOptions = $group->options->map(fn($o) => [
            'ingredient_id' => $o->ingredient_id,
            'quantity' => (float) $o->quantity,
        ])->toArray();
        if (empty($this->groupOptions)) {
            $this->groupOptions = [['ingredient_id' => '', 'quantity' => 1]];
        }
        $this->isGroupModalOpen = true;
    }

    public function addGroupOption()
    {
        $this->groupOptions[] = ['ingredient_id' => '', 'quantity' => 1];
    }

    public function removeGroupOption($index)
    {
        if (count($this->groupOptions) > 1) {
            array_splice($this->groupOptions, $index, 1);
            $this->groupOptions = array_values($this->groupOptions);
        }
    }

    public function storeGroup()
    {
        $isNew = !$this->groupId;
        if (!auth()->user()->hasPermission($isNew ? 'ingredients.create' : 'ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $this->validate([
            'groupName' => 'required|min:2',
            'groupOptions' => 'required|array|min:2',
            'groupOptions.*.ingredient_id' => 'required|exists:ingredients,id',
            'groupOptions.*.quantity' => 'required|numeric|min:0.001',
        ], [
            'groupOptions.min' => 'Un grupo debe tener al menos 2 opciones.',
            'groupOptions.*.ingredient_id.required' => 'Selecciona un ingrediente.',
            'groupOptions.*.quantity.required' => 'La cantidad es requerida.',
        ]);

        $branchId = $this->getBranchId();
        if (!$branchId) {
            $this->dispatch('notify', message: 'Debes tener una sucursal asignada', type: 'error');
            return;
        }

        // Check for duplicate ingredients
        $ingredientIds = array_column($this->groupOptions, 'ingredient_id');
        if (count($ingredientIds) !== count(array_unique($ingredientIds))) {
            $this->dispatch('notify', message: 'No se puede repetir un ingrediente en el mismo grupo', type: 'error');
            return;
        }

        $oldValues = $isNew ? null : IngredientGroup::find($this->groupId)?->toArray();

        $group = IngredientGroup::updateOrCreate(['id' => $this->groupId], [
            'branch_id' => $branchId,
            'name' => $this->groupName,
            'description' => $this->groupDescription,
            'is_active' => $this->groupIsActive,
        ]);

        // Sync options
        $group->options()->delete();
        foreach ($this->groupOptions as $i => $option) {
            $group->options()->create([
                'ingredient_id' => $option['ingredient_id'],
                'quantity' => $option['quantity'],
                'sort_order' => $i,
            ]);
        }

        $isNew
            ? ActivityLogService::logCreate('ingredients', $group, "Grupo '{$group->name}' creado")
            : ActivityLogService::logUpdate('ingredients', $group, $oldValues, "Grupo '{$group->name}' actualizado");

        $this->isGroupModalOpen = false;
        $this->dispatch('notify', message: $isNew ? 'Grupo creado' : 'Grupo actualizado');
    }

    public function toggleGroupStatus($id)
    {
        if (!auth()->user()->hasPermission('ingredients.edit')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $group = IngredientGroup::find($id);
        $oldValues = $group->toArray();
        $group->is_active = !$group->is_active;
        $group->save();
        ActivityLogService::logUpdate('ingredients', $group, $oldValues, "Grupo '{$group->name}' " . ($group->is_active ? 'activado' : 'desactivado'));
        $this->dispatch('notify', message: $group->is_active ? 'Activado' : 'Desactivado');
    }

    public function viewGroup($id)
    {
        $this->viewingGroup = IngredientGroup::with(['options.ingredient.unit'])->findOrFail($id);
        $this->isGroupDetailOpen = true;
    }

    // ─── Helpers ───

    private function resetForm()
    {
        $this->itemId = null;
        $this->name = '';
        $this->description = '';
        $this->category_id = '';
        $this->unit_id = '';
        $this->tax_id = '';
        $this->cost = 0;
        $this->sale_price = 0;
        $this->price_includes_tax = false;
        $this->available_for_sale = false;
        $this->current_stock = 0;
        $this->min_stock = 0;
        $this->max_stock = 0;
        $this->is_active = true;
    }

    private function resetGroupForm()
    {
        $this->groupId = null;
        $this->groupName = '';
        $this->groupDescription = '';
        $this->groupIsActive = true;
        $this->groupOptions = [['ingredient_id' => '', 'quantity' => 1]];
    }
}
