<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Brand;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class Kardex extends Component
{
    use WithPagination;

    // Filters
    public ?int $selectedBranchId = null;
    public ?int $selectedCategoryId = null;
    public ?int $selectedBrandId = null;
    public string $itemTypeFilter = 'all'; // all, product, ingredient
    public string $stockFilter = 'all'; // all, zero, positive, negative
    public string $search = '';
    public ?string $dateFrom = null;
    public ?string $dateTo = null;

    // Summary stats
    public int $totalProducts = 0;
    public int $productsWithStock = 0;
    public int $productsZeroStock = 0;
    public int $productsNegativeStock = 0;
    public float $totalInventoryValue = 0;
    public float $totalInventoryCost = 0;
    public float $totalPotentialProfit = 0;

    // Chart data
    public array $stockByCategory = [];
    public array $stockDistribution = [];
    public array $topValueProducts = [];
    public array $lowStockProducts = [];

    // Detail view
    public ?int $selectedItemId = null;
    public string $selectedItemType = 'product'; // product or ingredient
    public array $productMovements = [];
    public bool $isDetailModalOpen = false;

    public function mount()
    {
        $user = auth()->user();
        if (!$user->isSuperAdmin() && $user->branch_id) {
            $this->selectedBranchId = $user->branch_id;
        }

        $this->dateFrom = null;
        $this->dateTo = null;
    }

    private function getItemsQuery()
    {
        $productItems = collect();
        if (in_array($this->itemTypeFilter, ['all', 'product'])) {
            $pQuery = Product::query()->where('is_active', true);

            if ($this->selectedBranchId) {
                $pQuery->where('branch_id', $this->selectedBranchId);
            } elseif (!auth()->user()->isSuperAdmin()) {
                $pQuery->where('branch_id', auth()->user()->branch_id);
            }

            if ($this->selectedCategoryId) {
                $pQuery->where('category_id', $this->selectedCategoryId);
            }

            if ($this->selectedBrandId) {
                $pQuery->where('brand_id', $this->selectedBrandId);
            }

            if ($this->search) {
                $search = trim($this->search);
                $pQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%")
                      ->orWhere('barcode', 'like', "%{$search}%");
                });
            }

            switch ($this->stockFilter) {
                case 'zero':
                    $pQuery->where('current_stock', 0);
                    break;
                case 'positive':
                    $pQuery->where('current_stock', '>', 0);
                    break;
                case 'negative':
                    $pQuery->where('current_stock', '<', 0);
                    break;
            }

            $productItems = $pQuery->with(['category', 'brand', 'unit'])->get()->map(function ($p) {
                $stock = (float) ($p->current_stock ?? 0);
                $purchasePrice = (float) ($p->purchase_price ?? 0);
                $salePrice = (float) ($p->sale_price ?? 0);
                return (object) [
                    'id' => $p->id,
                    'item_type' => 'product',
                    'type_label' => 'Producto',
                    'name' => $p->name,
                    'sku' => $p->sku ?: '-',
                    'category_name' => $p->category?->name ?? '-',
                    'brand_name' => $p->brand?->name ?? '-',
                    'unit_symbol' => $p->unit?->abbreviation ?? 'und',
                    'current_stock' => $stock,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'inventory_value' => $stock * $salePrice,
                    'profit' => ($salePrice - $purchasePrice) * $stock,
                    'image' => $p->image,
                    'min_stock' => (float) ($p->min_stock ?? 0),
                ];
            });
        }

        $ingredientItems = collect();
        if (in_array($this->itemTypeFilter, ['all', 'ingredient'])) {
            $iQuery = Ingredient::query()->where('is_active', true);

            if ($this->selectedCategoryId) {
                $iQuery->where('category_id', $this->selectedCategoryId);
            }

            // Ingredients do not have brand, skip if brand filter selected
            if ($this->selectedBrandId) {
                $iQuery->whereRaw('1 = 0');
            }

            if ($this->search) {
                $search = trim($this->search);
                $iQuery->where('name', 'like', "%{$search}%");
            }

            switch ($this->stockFilter) {
                case 'zero':
                    $iQuery->where('stock', 0);
                    break;
                case 'positive':
                    $iQuery->where('stock', '>', 0);
                    break;
                case 'negative':
                    $iQuery->where('stock', '<', 0);
                    break;
            }

            $ingredientItems = $iQuery->with(['category', 'unit'])->get()->map(function ($i) {
                $stock = (float) ($i->stock ?? 0);
                $purchasePrice = (float) ($i->purchase_price ?? 0);
                $salePrice = (float) ($i->sale_price ?? 0);
                return (object) [
                    'id' => $i->id,
                    'item_type' => 'ingredient',
                    'type_label' => 'Ingrediente',
                    'name' => $i->name,
                    'sku' => 'ING-' . str_pad($i->id, 4, '0', STR_PAD_LEFT),
                    'category_name' => $i->category?->name ?? '-',
                    'brand_name' => '-',
                    'unit_symbol' => $i->unit?->abbreviation ?? 'und',
                    'current_stock' => $stock,
                    'purchase_price' => $purchasePrice,
                    'sale_price' => $salePrice,
                    'inventory_value' => $stock * $salePrice,
                    'profit' => ($salePrice - $purchasePrice) * $stock,
                    'image' => null,
                    'min_stock' => 0,
                ];
            });
        }

        return $productItems->concat($ingredientItems)->sortBy('name')->values();
    }

    private function calculateSummary()
    {
        $all = $this->getItemsQuery();

        $this->totalProducts = $all->count();
        $this->productsWithStock = $all->where('current_stock', '>', 0)->count();
        $this->productsZeroStock = $all->where('current_stock', '==', 0)->count();
        $this->productsNegativeStock = $all->where('current_stock', '<', 0)->count();

        $this->totalInventoryValue = $all->where('current_stock', '>', 0)->sum('inventory_value');
        $this->totalInventoryCost = $all->where('current_stock', '>', 0)->sum(function ($item) {
            return $item->current_stock * $item->purchase_price;
        });
        $this->totalPotentialProfit = $this->totalInventoryValue - $this->totalInventoryCost;
    }

    private function loadChartData()
    {
        $all = $this->getItemsQuery();

        // Stock by category
        $byCat = [];
        foreach ($all as $item) {
            $cat = $item->category_name;
            if (!isset($byCat[$cat])) {
                $byCat[$cat] = ['category_name' => $cat, 'total_stock' => 0, 'product_count' => 0];
            }
            $byCat[$cat]['total_stock'] += $item->current_stock;
            $byCat[$cat]['product_count']++;
        }
        usort($byCat, fn($a, $b) => $b['total_stock'] <=> $a['total_stock']);
        $this->stockByCategory = array_slice($byCat, 0, 8);

        // Stock distribution
        $this->stockDistribution = [
            ['label' => 'Con existencias', 'value' => $this->productsWithStock, 'color' => '#22c55e'],
            ['label' => 'Sin existencias', 'value' => $this->productsZeroStock, 'color' => '#f59e0b'],
            ['label' => 'Stock negativo', 'value' => $this->productsNegativeStock, 'color' => '#ef4444'],
        ];

        // Top 10 by inventory value
        $this->topValueProducts = $all->where('current_stock', '>', 0)
            ->sortByDesc('inventory_value')
            ->take(10)
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'item_type' => $item->item_type,
                    'name' => $item->name,
                    'sku' => $item->sku,
                    'current_stock' => $item->current_stock,
                    'sale_price' => $item->sale_price,
                    'inventory_value' => $item->inventory_value,
                ];
            })
            ->values()
            ->toArray();

        // Low stock products/ingredients
        $this->lowStockProducts = $all->filter(function ($item) {
            if ($item->item_type === 'product' && $item->min_stock > 0) {
                return $item->current_stock <= $item->min_stock;
            }
            if ($item->item_type === 'ingredient') {
                return $item->current_stock <= 5;
            }
            return false;
        })
        ->sortBy('current_stock')
        ->take(10)
        ->map(function ($item) {
            return [
                'id' => $item->id,
                'item_type' => $item->item_type,
                'name' => $item->name,
                'sku' => $item->sku,
                'current_stock' => $item->current_stock,
                'min_stock' => $item->min_stock,
            ];
        })
        ->values()
        ->toArray();
    }

    public function viewProductKardex(int $id, string $type = 'product')
    {
        $this->selectedItemId = $id;
        $this->selectedItemType = $type;
        $this->dateFrom = null;
        $this->dateTo = null;
        $this->loadProductMovements();
        $this->isDetailModalOpen = true;
    }

    public function closeDetailModal()
    {
        $this->isDetailModalOpen = false;
        $this->selectedItemId = null;
        $this->productMovements = [];
    }

    public function updatedItemTypeFilter() { $this->resetPage(); }
    public function updatedStockFilter() { $this->resetPage(); }
    public function updatedSearch() { $this->resetPage(); }
    public function updatedSelectedBranchId() { $this->resetPage(); }
    public function updatedSelectedCategoryId() { $this->resetPage(); }
    public function updatedSelectedBrandId() { $this->resetPage(); }

    public function updatedDateFrom()
    {
        if ($this->selectedItemId) {
            $this->loadProductMovements();
        }
    }

    public function updatedDateTo()
    {
        if ($this->selectedItemId) {
            $this->loadProductMovements();
        }
    }

    private function loadProductMovements()
    {
        if (!$this->selectedItemId) return;

        if ($this->selectedItemType === 'ingredient') {
            $query = InventoryMovement::where('ingredient_id', $this->selectedItemId)
                ->with(['systemDocument', 'user', 'branch']);
        } else {
            $query = InventoryMovement::where('product_id', $this->selectedItemId)
                ->with(['systemDocument', 'user', 'branch']);
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        $this->productMovements = $query
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(function ($movement) {
                $invoiceNumber = null;
                $receiptUrl = null;
                if ($movement->reference_type === 'App\\Models\\Sale' && $movement->reference_id) {
                    $sale = \App\Models\Sale::find($movement->reference_id);
                    if ($sale) {
                        $invoiceNumber = $sale->invoice_number;
                        $receiptUrl = route('receipt.show', $sale->id);
                    }
                } elseif ($movement->reference_type === 'App\\Models\\Refund' && $movement->reference_id) {
                    $refund = \App\Models\Refund::find($movement->reference_id);
                    if ($refund) {
                        $invoiceNumber = $refund->number;
                        $receiptUrl = route('refund-receipt.show', $refund->id);
                    }
                } elseif ($movement->reference_type === 'App\\Models\\Purchase' && $movement->reference_id) {
                    $purchase = \App\Models\Purchase::find($movement->reference_id);
                    if ($purchase) {
                        $invoiceNumber = $purchase->purchase_number ?? $movement->document_number;
                        $receiptUrl = route('purchase-receipt.show', $purchase->id);
                    }
                }

                return [
                    'id' => $movement->id,
                    'date' => $movement->created_at->format('d/m/Y H:i'),
                    'document' => $movement->systemDocument?->name ?? 'N/A',
                    'document_number' => $movement->document_number,
                    'invoice_number' => $invoiceNumber,
                    'receipt_url' => $receiptUrl,
                    'type' => $movement->movement_type,
                    'quantity' => $movement->quantity,
                    'stock_before' => $movement->stock_before,
                    'stock_after' => $movement->stock_after,
                    'unit_cost' => $movement->unit_cost,
                    'total_cost' => $movement->total_cost,
                    'user' => $movement->user?->name ?? 'Sistema',
                    'notes' => $movement->notes,
                ];
            })
            ->toArray();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->itemTypeFilter = 'all';
        $this->stockFilter = 'all';
        $this->selectedCategoryId = null;
        $this->selectedBrandId = null;
        $this->dateFrom = null;
        $this->dateTo = null;
        if (auth()->user()->isSuperAdmin()) {
            $this->selectedBranchId = null;
        }
        $this->resetPage();
    }

    public function reconstructIngredientMovements()
    {
        try {
            \Illuminate\Support\Facades\Artisan::call('inventory:reconstruct-ingredient-movements', ['--force' => true]);
            $this->dispatch('notify', message: 'Se reconstruyeron los movimientos de ventas históricas para ingredientes correctamente', type: 'success');
            if ($this->selectedItemId) {
                $this->loadProductMovements();
            }
        } catch (\Exception $e) {
            $this->dispatch('notify', message: 'Error al reconstruir movimientos: ' . $e->getMessage(), type: 'error');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $isSuperAdmin = $user->isSuperAdmin();

        $this->calculateSummary();
        $this->loadChartData();

        $allCollection = $this->getItemsQuery();
        $currentPage = LengthAwarePaginator::resolveCurrentPage() ?: 1;
        $perPage = 15;
        $pageItems = $allCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $products = new LengthAwarePaginator(
            $pageItems,
            $allCollection->count(),
            $perPage,
            $currentPage,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        $branches = $isSuperAdmin ? Branch::where('is_active', true)->orderBy('name')->get() : collect();
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $brands = Brand::where('is_active', true)->orderBy('name')->get();

        // Selected item for modal
        $selectedProduct = null;
        if ($this->selectedItemId) {
            if ($this->selectedItemType === 'ingredient') {
                $ing = Ingredient::with(['category', 'unit'])->find($this->selectedItemId);
                if ($ing) {
                    $selectedProduct = (object) [
                        'id' => $ing->id,
                        'name' => $ing->name,
                        'sku' => 'ING-' . str_pad($ing->id, 4, '0', STR_PAD_LEFT),
                        'category' => $ing->category,
                        'current_stock' => (float) ($ing->stock ?? 0),
                        'unit' => $ing->unit,
                        'image' => null,
                        'item_type' => 'ingredient',
                        'type_label' => 'Ingrediente',
                    ];
                }
            } else {
                $prod = Product::with(['category', 'brand', 'unit'])->find($this->selectedItemId);
                if ($prod) {
                    $selectedProduct = (object) [
                        'id' => $prod->id,
                        'name' => $prod->name,
                        'sku' => $prod->sku ?: '-',
                        'category' => $prod->category,
                        'current_stock' => (float) ($prod->current_stock ?? 0),
                        'unit' => $prod->unit,
                        'image' => $prod->image,
                        'item_type' => 'product',
                        'type_label' => 'Producto',
                    ];
                }
            }
        }

        return view('livewire.reports.kardex', [
            'products' => $products,
            'branches' => $branches,
            'categories' => $categories,
            'brands' => $brands,
            'isSuperAdmin' => $isSuperAdmin,
            'selectedProduct' => $selectedProduct,
        ]);
    }
}
