<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductBarcode;
use App\Models\ProductChild;
use App\Models\SystemDocument;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class InventoryAdjustments extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = '';
    public ?string $filterBranch = null;
    
    public $isModalOpen = false;
    public $isDeleteModalOpen = false;
    public $isViewModalOpen = false;
    public $documentToDelete = null;
    public $viewingDocument = null;

    public $notes = '';
    public $items = []; // Each item has: product_id, name, sku, current_stock, quantity, type (in/out)
    public ?int $branch_id = null;

    public $productSearch = '';
    public $showProductDropdown = false;
    public $barcodeSearch = '';

    // Branch control
    public bool $needsBranchSelection = false;
    public $branches = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;
        
        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
        }
    }

    public function render()
    {
        $user = auth()->user();
        $adjustmentDoc = SystemDocument::findByCode('adjustment');
        
        $query = InventoryMovement::query()
            ->when($adjustmentDoc, fn($q) => $q->where('system_document_id', $adjustmentDoc->id))
            ->when(!$adjustmentDoc, fn($q) => $q->whereRaw('1 = 0'));

        // Apply branch filter
        if ($this->needsBranchSelection) {
            if ($this->filterBranch) {
                $query->where('branch_id', $this->filterBranch);
            }
        } else {
            $query->where('branch_id', $user->branch_id);
        }

        $documents = $query
            ->when(trim($this->search), function ($q) {
                $search = trim($this->search);
                $q->where(function($query) use ($search) {
                    $query->where('document_number', 'like', "%{$search}%")
                        ->orWhere('notes', 'like', "%{$search}%")
                        ->orWhereHas('product', fn($pq) => $pq->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($this->filterType, fn($q) => $q->where('movement_type', $this->filterType))
            ->select('document_number', 'notes', 'user_id', 'branch_id', 'created_at')
            ->selectRaw('COUNT(*) as items_count')
            ->selectRaw('SUM(CASE WHEN movement_type = "in" THEN quantity ELSE 0 END) as total_in')
            ->selectRaw('SUM(CASE WHEN movement_type = "out" THEN quantity ELSE 0 END) as total_out')
            ->with(['user', 'branch'])
            ->groupBy('document_number', 'notes', 'user_id', 'branch_id', 'created_at')
            ->latest('created_at')
            ->paginate(15);

        $products = Product::query()
            ->active()
            ->when($this->productSearch, fn($q) => $q->where('name', 'like', "%{$this->productSearch}%")
                ->orWhere('sku', 'like', "%{$this->productSearch}%"))
            ->limit(10)
            ->get()
            ->map(function ($p) {
                return (object)[
                    'id' => $p->id,
                    'type' => 'product',
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'current_stock' => $p->current_stock,
                ];
            });

        $ingredients = \App\Models\Ingredient::query()
            ->where('is_active', true)
            ->where('manage_inventory', true)
            ->when($this->productSearch, fn($q) => $q->where('name', 'like', "%{$this->productSearch}%"))
            ->limit(10)
            ->get()
            ->map(function ($i) {
                return (object)[
                    'id' => $i->id,
                    'type' => 'ingredient',
                    'name' => $i->name,
                    'sku' => 'Ingrediente',
                    'current_stock' => $i->stock,
                ];
            });

        $searchResults = $products->concat($ingredients)->sortBy('name')->take(10);

        return view('livewire.inventory-adjustments', [
            'documents' => $documents,
            'searchResults' => $searchResults,
            'hasAdjustmentDocument' => (bool) $adjustmentDoc,
        ]);
    }

    public function create()
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->resetValidation();
        $this->resetForm();
        
        // Set default branch for users with assigned branch
        $user = auth()->user();
        if (!$this->needsBranchSelection && $user->branch_id) {
            $this->branch_id = $user->branch_id;
        }
        
        $this->isModalOpen = true;
    }

    public function updatedProductSearch()
    {
        $this->showProductDropdown = strlen($this->productSearch) >= 2;
    }

    public function addItem($itemId, $itemType = 'product')
    {
        $itemModel = $itemType === 'product' ? Product::find($itemId) : \App\Models\Ingredient::find($itemId);
        if (!$itemModel) return;

        foreach ($this->items as $item) {
            if (isset($item['item_id']) && $item['item_id'] == $itemId && $item['item_type'] == $itemType) {
                $this->dispatch('notify', message: 'Este item ya está en la lista', type: 'warning');
                return;
            } elseif (!isset($item['item_id']) && isset($item['product_id']) && $item['product_id'] == $itemId && $itemType == 'product') {
                $this->dispatch('notify', message: 'Este item ya está en la lista', type: 'warning');
                return;
            }
        }

        $this->items[] = [
            'product_id' => $itemType === 'product' ? $itemModel->id : null,
            'ingredient_id' => $itemType === 'ingredient' ? $itemModel->id : null,
            'item_id' => $itemModel->id,
            'item_type' => $itemType,
            'name' => $itemModel->name,
            'sku' => $itemType === 'product' ? $itemModel->sku : 'Ingrediente',
            'current_stock' => $itemType === 'product' ? ($itemModel->current_stock ?? 0) : ($itemModel->stock ?? 0),
            'quantity' => 1,
            'type' => 'in', // Default to entrada
        ];

        $this->productSearch = '';
        $this->showProductDropdown = false;
    }

    public function searchByBarcode()
    {
        $barcode = trim($this->barcodeSearch);

        if (strlen($barcode) < 3) {
            return;
        }

        // Determine branch for filtering
        $user = auth()->user();
        $branchId = $this->branch_id ?? $user->branch_id;

        // Search in product_barcodes table first
        $barcodeRecord = ProductBarcode::where('barcode', $barcode)->first();

        if ($barcodeRecord) {
            if ($barcodeRecord->product_child_id) {
                $child = ProductChild::where('id', $barcodeRecord->product_child_id)
                    ->where('is_active', true)
                    ->whereHas('product', fn($q) => $q->where('is_active', true)->forBranch($branchId))
                    ->first();

                if ($child) {
                    $this->addItem($child->product_id, 'product');
                    $this->barcodeSearch = '';
                    $this->dispatch('focus-barcode-adjustment');
                    return;
                }
            }

            if ($barcodeRecord->product_id) {
                $product = Product::where('id', $barcodeRecord->product_id)
                    ->where('is_active', true)
                    ->forBranch($branchId)
                    ->first();

                if ($product) {
                    $this->addItem($product->id, 'product');
                    $this->barcodeSearch = '';
                    $this->dispatch('focus-barcode-adjustment');
                    return;
                }
            }
        }

        // Fallback: search in legacy barcode fields
        $child = ProductChild::where('barcode', $barcode)
            ->where('is_active', true)
            ->whereHas('product', fn($q) => $q->where('is_active', true)->forBranch($branchId))
            ->first();

        if ($child) {
            $this->addItem($child->product_id, 'product');
            $this->barcodeSearch = '';
            $this->dispatch('focus-barcode-adjustment');
            return;
        }

        $product = Product::where('barcode', $barcode)
            ->where('is_active', true)
            ->forBranch($branchId)
            ->first();

        if ($product) {
            $this->addItem($product->id, 'product');
            $this->barcodeSearch = '';
            $this->dispatch('focus-barcode-adjustment');
            return;
        }

        $this->dispatch('notify', message: 'Producto no encontrado: ' . $barcode, type: 'warning');
        $this->barcodeSearch = '';
        $this->dispatch('focus-barcode-adjustment');
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function updateItemType($index, $type)
    {
        if (isset($this->items[$index]) && in_array($type, ['in', 'out'])) {
            $this->items[$index]['type'] = $type;
        }
    }

    public function updateQuantity($index, $quantity)
    {
        if (isset($this->items[$index])) {
            $this->items[$index]['quantity'] = max(1, (int) $quantity);
        }
    }

    public function store()
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.create')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        // Validate branch selection for super_admin
        if ($this->needsBranchSelection && !$this->branch_id) {
            $this->dispatch('notify', message: 'Debe seleccionar una sucursal', type: 'error');
            return;
        }

        if (empty($this->items)) {
            $this->dispatch('notify', message: 'Debes agregar al menos un producto', type: 'error');
            return;
        }

        // Validate stock for outgoing items
        foreach ($this->items as $item) {
            if ($item['type'] === 'out' && $item['current_stock'] < $item['quantity']) {
                $this->dispatch('notify', message: "Stock insuficiente para {$item['name']}. Stock: {$item['current_stock']}", type: 'error');
                return;
            }
        }

        $adjustmentDoc = SystemDocument::findByCode('adjustment');
        if (!$adjustmentDoc) {
            $this->dispatch('notify', message: 'No existe el documento de ajuste. Créalo en Documentos Sistema.', type: 'error');
            return;
        }

        // Determine branch_id
        $branchId = $this->needsBranchSelection ? $this->branch_id : auth()->user()->branch_id;

        DB::beginTransaction();
        try {
            $documentNumber = $adjustmentDoc->generateNextNumber();

            foreach ($this->items as $item) {
                $isProduct = ($item['item_type'] ?? 'product') === 'product';
                
                if ($isProduct) {
                    $model = Product::find($item['item_id'] ?? $item['product_id']);
                } else {
                    $model = \App\Models\Ingredient::find($item['item_id']);
                }
                
                if (!$model) continue;

                $stockBefore = $isProduct ? ($model->current_stock ?? 0) : ($model->stock ?? 0);
                $stockAfter = $item['type'] === 'in' 
                    ? $stockBefore + $item['quantity'] 
                    : $stockBefore - $item['quantity'];

                InventoryMovement::create([
                    'system_document_id' => $adjustmentDoc->id,
                    'document_number' => $documentNumber,
                    'product_id' => $isProduct ? $model->id : null,
                    'ingredient_id' => !$isProduct ? $model->id : null,
                    'branch_id' => $branchId,
                    'user_id' => auth()->id(),
                    'movement_type' => $item['type'],
                    'quantity' => $item['quantity'],
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'unit_cost' => $model->purchase_price,
                    'total_cost' => $model->purchase_price * $item['quantity'],
                    'notes' => $this->notes,
                    'movement_date' => now(),
                ]);

                if ($isProduct) {
                    $model->current_stock = $stockAfter;
                } else {
                    $model->stock = $stockAfter;
                }
                $model->save();
            }

            DB::commit();

            $totalIn = collect($this->items)->where('type', 'in')->sum('quantity');
            $totalOut = collect($this->items)->where('type', 'out')->sum('quantity');
            $itemCount = count($this->items);
            
            // Log using the first movement created
            $firstMovement = InventoryMovement::where('document_number', $documentNumber)->first();
            if ($firstMovement) {
                ActivityLogService::logCreate(
                    'inventory_adjustments', 
                    $firstMovement, 
                    "Ajuste {$documentNumber}: {$itemCount} productos (+{$totalIn}/-{$totalOut})"
                );
            }

            $this->isModalOpen = false;
            $this->dispatch('notify', message: "Ajuste {$documentNumber} registrado correctamente");

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function viewDocument($documentNumber)
    {
        $this->viewingDocument = InventoryMovement::where('document_number', $documentNumber)
            ->with(['product', 'ingredient', 'user'])
            ->get();
        $this->isViewModalOpen = true;
    }

    public function confirmDelete($documentNumber)
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }
        $this->documentToDelete = $documentNumber;
        $this->isDeleteModalOpen = true;
    }

    public function delete()
    {
        if (!auth()->user()->hasPermission('inventory_adjustments.delete')) {
            $this->dispatch('notify', message: 'No tienes permiso', type: 'error');
            return;
        }

        $movements = InventoryMovement::where('document_number', $this->documentToDelete)->get();
        
        if ($movements->isEmpty()) {
            $this->dispatch('notify', message: 'Documento no encontrado', type: 'error');
            $this->isDeleteModalOpen = false;
            return;
        }

        DB::beginTransaction();
        try {
            // Save first movement for logging before deletion
            $firstMovement = $movements->first();
            
            foreach ($movements as $movement) {
                if ($movement->product_id) {
                    $product = $movement->product;
                    if ($product) {
                        $product->current_stock = $movement->movement_type === 'in'
                            ? $product->current_stock - $movement->quantity
                            : $product->current_stock + $movement->quantity;
                        $product->save();
                    }
                } elseif ($movement->ingredient_id) {
                    $ingredient = $movement->ingredient;
                    if ($ingredient) {
                        $ingredient->stock = $movement->movement_type === 'in'
                            ? $ingredient->stock - $movement->quantity
                            : $ingredient->stock + $movement->quantity;
                        $ingredient->save();
                    }
                }
                $movement->delete();
            }

            DB::commit();

            // Log using the saved reference
            if ($firstMovement) {
                ActivityLogService::logDelete(
                    'inventory_adjustments', 
                    $firstMovement, 
                    "Ajuste eliminado: {$this->documentToDelete}"
                );
            }

            $this->isDeleteModalOpen = false;
            $this->dispatch('notify', message: 'Ajuste eliminado y stock revertido');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterType = '';
        $this->filterBranch = null;
        $this->resetPage();
    }

    private function resetForm()
    {
        $this->notes = '';
        $this->items = [];
        $this->branch_id = null;
        $this->productSearch = '';
        $this->barcodeSearch = '';
        $this->showProductDropdown = false;
    }
}
