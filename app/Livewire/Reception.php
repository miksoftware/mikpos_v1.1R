<?php

namespace App\Livewire;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\RestaurantTable;
use App\Models\Service;
use App\Models\TableOrder;
use App\Models\TableOrderItem;
use App\Models\Zone;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Reception extends Component
{
    // ─── Reception view ───
    public ?string $filterBranch = null;
    public bool $needsBranchSelection = false;
    public $branches = [];
    public ?int $activeZone = null;

    // ─── Table POS view ───
    public ?int $selectedTableId = null;
    public ?string $selectedCategory = null;
    public string $productSearch = '';
    public string $observations = '';
    public array $cart = [];

    // ─── Composite product modal ───
    public bool $showCompositeModal = false;
    public ?int $compositeProductId = null;
    public string $compositeProductName = '';
    public float $compositeProductPrice = 0;
    public string $compositeProductUnit = 'UND';
    public array $compositeGroups = [];
    public array $selectedGroupOptions = [];

    public function mount()
    {
        $user = auth()->user();
        $this->needsBranchSelection = $user->isSuperAdmin() || !$user->branch_id;

        if ($this->needsBranchSelection) {
            $this->branches = Branch::where('is_active', true)->orderBy('name')->get();
            if ($this->branches->count() === 1) {
                $this->filterBranch = (string) $this->branches->first()->id;
            }
        }

        $this->selectFirstZone();
    }

    public function updatedFilterBranch()
    {
        $this->activeZone = null;
        $this->selectFirstZone();
    }

    public function switchZone(int $zoneId)
    {
        $this->activeZone = $zoneId;
    }

    // ─── Open / Close table ───

    public function openTable(int $tableId)
    {
        $this->selectedTableId = $tableId;
        $this->selectedCategory = null;
        $this->productSearch = '';
        $this->cart = [];
        $this->observations = '';

        // Load existing open order if table is occupied
        $table = RestaurantTable::find($tableId);
        if ($table) {
            $activeOrder = TableOrder::where('restaurant_table_id', $tableId)
                ->where('status', 'open')
                ->with('items')
                ->latest()
                ->first();

            if ($activeOrder) {
                $this->observations = $activeOrder->observations ?? '';
                foreach ($activeOrder->items as $item) {
                    $prefix = match ($item->item_type) {
                        'service' => 's_',
                        'ingredient' => 'i_',
                        default => 'p_',
                    };
                    $key = $prefix . $item->item_id;
                    if ($item->group_selections) {
                        $key .= '_' . implode('_', array_values($item->group_selections));
                    }

                    $this->cart[$key] = [
                        'type' => $item->item_type,
                        'id' => $item->item_id,
                        'name' => $item->item_name,
                        'unit_price' => (float) $item->unit_price,
                        'quantity' => (float) $item->quantity,
                        'subtotal' => (float) $item->subtotal,
                        'unit' => '',
                        'group_selections' => $item->group_selections,
                    ];
                }
            }
        }
    }

    public function backToReception()
    {
        $this->selectedTableId = null;
        $this->cart = [];
        $this->observations = '';
        $this->productSearch = '';
        $this->selectedCategory = null;
    }

    // ─── Category filter ───

    public function selectCategory($categoryId)
    {
        $this->selectedCategory = $this->selectedCategory == $categoryId ? null : $categoryId;
    }

    // ─── Cart operations ───

    public function addToCart(int $productId)
    {
        $product = Product::with(['tax', 'unit', 'productIngredientGroups.ingredientGroup.options.ingredient'])->find($productId);
        if (!$product) return;

        $price = $product->price_includes_tax ? (float) $product->sale_price : $product->getSalePriceWithTax();
        $unit = $product->unit?->abbreviation ?? 'UND';

        // If composite with ingredient groups, show modal
        if ($product->isComposite() && $product->productIngredientGroups->count() > 0) {
            $this->compositeProductId = $product->id;
            $this->compositeProductName = $product->name;
            $this->compositeProductPrice = $price;
            $this->compositeProductUnit = $unit;
            $this->compositeGroups = [];
            $this->selectedGroupOptions = [];

            foreach ($product->productIngredientGroups as $pig) {
                $group = $pig->ingredientGroup;
                if (!$group) continue;

                $options = [];
                foreach ($group->options as $opt) {
                    $options[] = [
                        'id' => $opt->ingredient_id,
                        'name' => $opt->ingredient->name ?? 'Sin nombre',
                    ];
                }

                $this->compositeGroups[] = [
                    'group_id' => $group->id,
                    'group_name' => $group->name,
                    'options' => $options,
                ];

                // Pre-select first option
                if (count($options) > 0) {
                    $this->selectedGroupOptions[$group->id] = $options[0]['id'];
                }
            }

            $this->showCompositeModal = true;
            return;
        }

        $key = 'p_' . $productId;
        $this->addItemToCart($key, 'product', $product->id, $product->name, $price, $unit);
    }

    public function confirmCompositeProduct()
    {
        if (!$this->compositeProductId) return;

        // Build selections label
        $selections = [];
        foreach ($this->compositeGroups as $group) {
            $gid = $group['group_id'];
            $selectedId = $this->selectedGroupOptions[$gid] ?? null;
            if ($selectedId) {
                foreach ($group['options'] as $opt) {
                    if ($opt['id'] == $selectedId) {
                        $selections[] = $opt['name'];
                        break;
                    }
                }
            }
        }

        $selectionsKey = implode('_', array_values($this->selectedGroupOptions));
        $key = 'p_' . $this->compositeProductId . '_' . $selectionsKey;
        $label = $this->compositeProductName;
        if (count($selections) > 0) {
            $label .= ' (' . implode(', ', $selections) . ')';
        }

        $this->addItemToCart($key, 'product', $this->compositeProductId, $label,
            $this->compositeProductPrice, $this->compositeProductUnit);

        // Store selections in cart item
        $this->cart[$key]['group_selections'] = $this->selectedGroupOptions;

        $this->closeCompositeModal();
    }

    public function closeCompositeModal()
    {
        $this->showCompositeModal = false;
        $this->compositeProductId = null;
        $this->compositeProductName = '';
        $this->compositeProductPrice = 0;
        $this->compositeProductUnit = 'UND';
        $this->compositeGroups = [];
        $this->selectedGroupOptions = [];
    }

    public function addServiceToCart(int $serviceId)
    {
        $service = Service::with(['tax'])->find($serviceId);
        if (!$service) return;

        $key = 's_' . $serviceId;
        $this->addItemToCart($key, 'service', $service->id, $service->name,
            $service->price_includes_tax ? (float) $service->sale_price : $service->getSalePriceWithTax(),
            'SRV');
    }

    public function addIngredientToCart(int $ingredientId)
    {
        $ingredient = Ingredient::with(['tax', 'unit'])->find($ingredientId);
        if (!$ingredient || !$ingredient->available_for_sale) return;

        $key = 'i_' . $ingredientId;
        $this->addItemToCart($key, 'ingredient', $ingredient->id, $ingredient->name,
            $ingredient->price_includes_tax ? (float) $ingredient->sale_price : $ingredient->getSalePriceWithTax(),
            $ingredient->unit?->abbreviation ?? 'UND');
    }

    private function addItemToCart(string $key, string $type, int $id, string $name, float $price, string $unit): void
    {
        if (isset($this->cart[$key])) {
            $this->cart[$key]['quantity']++;
            $this->cart[$key]['subtotal'] = $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
        } else {
            $this->cart[$key] = [
                'type' => $type,
                'id' => $id,
                'name' => $name,
                'unit_price' => $price,
                'quantity' => 1,
                'subtotal' => $price,
                'unit' => $unit,
            ];
        }
    }

    public function incrementItem(string $key)
    {
        if (!isset($this->cart[$key])) return;
        $this->cart[$key]['quantity']++;
        $this->cart[$key]['subtotal'] = $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
    }

    public function decrementItem(string $key)
    {
        if (!isset($this->cart[$key])) return;
        if ($this->cart[$key]['quantity'] <= 1) {
            unset($this->cart[$key]);
            return;
        }
        $this->cart[$key]['quantity']--;
        $this->cart[$key]['subtotal'] = $this->cart[$key]['quantity'] * $this->cart[$key]['unit_price'];
    }

    public function removeItem(string $key)
    {
        unset($this->cart[$key]);
    }

    public function clearCart()
    {
        $this->cart = [];
        $this->observations = '';
    }

    // ─── Confirm order ───

    public function confirmOrder()
    {
        if (empty($this->cart) || !$this->selectedTableId) {
            $this->dispatch('notify', message: 'No hay items en el pedido', type: 'error');
            return;
        }

        $table = RestaurantTable::find($this->selectedTableId);
        if (!$table) return;

        $branchId = $this->getBranchId();
        $total = $this->getCartTotal();

        // Close any existing open order for this table
        TableOrder::where('restaurant_table_id', $table->id)
            ->where('status', 'open')
            ->update(['status' => 'closed']);

        // Create new order
        $order = TableOrder::create([
            'restaurant_table_id' => $table->id,
            'user_id' => auth()->id(),
            'branch_id' => $branchId,
            'status' => 'open',
            'observations' => $this->observations ?: null,
            'total' => $total,
        ]);

        // Create order items
        foreach ($this->cart as $key => $item) {
            TableOrderItem::create([
                'table_order_id' => $order->id,
                'item_type' => $item['type'],
                'item_id' => $item['id'],
                'item_name' => $item['name'],
                'unit_price' => $item['unit_price'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal'],
                'group_selections' => $item['group_selections'] ?? null,
            ]);
        }

        // Update table status to occupied
        $table->update(['status' => 'occupied']);

        // Reset and go back to reception
        $this->dispatch('notify', message: 'Pedido confirmado para ' . $table->name, type: 'success');
        $this->backToReception();
    }

    public function getCartTotal(): float
    {
        return collect($this->cart)->sum('subtotal');
    }

    public function getCartItemCount(): int
    {
        return (int) collect($this->cart)->sum('quantity');
    }

    // ─── Helpers ───

    private function selectFirstZone(): void
    {
        $branchId = $this->getBranchId();
        if (!$branchId) return;

        $first = Zone::where('is_active', true)
            ->where('branch_id', $branchId)
            ->orderBy('name')
            ->first();

        if ($first) {
            $this->activeZone = $first->id;
        }
    }

    private function getBranchId(): ?int
    {
        if ($this->needsBranchSelection) {
            return $this->filterBranch ? (int) $this->filterBranch : null;
        }
        return auth()->user()->branch_id;
    }

    // ─── Render ───

    public function render()
    {
        $branchId = $this->getBranchId();

        if ($this->selectedTableId) {
            return $this->renderTablePos($branchId);
        }

        return $this->renderReception($branchId);
    }

    private function renderTablePos(?int $branchId)
    {
        $table = RestaurantTable::with('zone')->find($this->selectedTableId);
        $search = trim($this->productSearch);
        $hasSearch = strlen($search) >= 2;

        // Products
        $products = Product::with(['category', 'brand', 'tax', 'unit'])
            ->where('is_active', true)
            ->where('current_stock', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($hasSearch, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            }))
            ->orderBy('name')->limit(50)->get();

        // Services
        $services = Service::with(['category', 'tax'])
            ->where('is_active', true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($hasSearch, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            }))
            ->orderBy('name')->limit(20)->get();

        // Ingredients available for sale
        $ingredients = Ingredient::with(['category', 'tax', 'unit'])
            ->where('is_active', true)
            ->where('available_for_sale', true)
            ->where('current_stock', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->when($hasSearch, fn($q) => $q->where(function ($q2) use ($search) {
                $q2->where('name', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%");
            }))
            ->orderBy('name')->limit(30)->get();

        // Only categories that have available products, services, or ingredients
        $categoryIdsFromProducts = Product::where('is_active', true)
            ->where('current_stock', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('category_id')
            ->pluck('category_id');

        $categoryIdsFromServices = Service::where('is_active', true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('category_id')
            ->pluck('category_id');

        $categoryIdsFromIngredients = Ingredient::where('is_active', true)
            ->where('available_for_sale', true)
            ->where('current_stock', '>', 0)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereNotNull('category_id')
            ->pluck('category_id');

        $activeCategoryIds = $categoryIdsFromProducts
            ->merge($categoryIdsFromServices)
            ->merge($categoryIdsFromIngredients)
            ->unique();

        $categories = Category::where('is_active', true)
            ->whereIn('id', $activeCategoryIds)
            ->orderBy('name')->get();

        return view('livewire.reception', [
            'table' => $table,
            'categories' => $categories,
            'products' => $products,
            'services' => $services,
            'ingredients' => $ingredients,
            'cartTotal' => $this->getCartTotal(),
            'cartItemCount' => $this->getCartItemCount(),
            'zones' => collect(), 'currentZone' => null, 'tables' => collect(),
            'totalAll' => 0, 'availableAll' => 0, 'occupiedAll' => 0, 'reservedAll' => 0,
        ]);
    }

    private function renderReception(?int $branchId)
    {
        $zones = Zone::query()
            ->where('is_active', true)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->withCount(['tables' => fn($q) => $q->where('is_active', true)])
            ->withCount(['tables as available_count' => fn($q) => $q->where('is_active', true)->where('status', 'available')])
            ->withCount(['tables as occupied_count' => fn($q) => $q->where('is_active', true)->where('status', 'occupied')])
            ->withCount(['tables as reserved_count' => fn($q) => $q->where('is_active', true)->where('status', 'reserved')])
            ->orderBy('name')->get();

        $currentZone = null;
        $tables = collect();

        if ($this->activeZone) {
            $currentZone = $zones->firstWhere('id', $this->activeZone);
            if ($currentZone) {
                $tables = $currentZone->tables()
                    ->where('is_active', true)
                    ->with(['activeOrder'])
                    ->orderBy('name')->get();
            }
        }

        return view('livewire.reception', [
            'zones' => $zones,
            'currentZone' => $currentZone,
            'tables' => $tables,
            'totalAll' => $zones->sum('tables_count'),
            'availableAll' => $zones->sum('available_count'),
            'occupiedAll' => $zones->sum('occupied_count'),
            'reservedAll' => $zones->sum('reserved_count'),
        ]);
    }
}
