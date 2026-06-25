<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Mesa;
use App\Models\Sector;
use App\Models\Cuenta;
use App\Models\CuentaItem;
use App\Models\CuentaItemSelectedIngredient;
use App\Models\Product;
use App\Models\Ingredient;
use App\Models\IngredientGroup;
use App\Models\Category;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\PaymentMethod;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashReconciliation;
use App\Models\InventoryMovement;
use App\Models\KitchenOrder;
use App\Models\KitchenOrderItem;
use App\Models\PreparationStation;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

#[Layout('layouts.app')]
class Mostrador extends Component
{
    // ─── Navigation ───────────────────────────────────────────────────────────
    public string $view = 'mesas';         // 'mesas' | 'orden'
    public $selectedSectorId = null;       // pill filter in mesas view

    // ─── Active order ─────────────────────────────────────────────────────────
    public $selectedMesaId = null;
    public $selectedMesaName = '';
    public $selectedSectorName = '';
    public $currentCuentaId = null;

    // ─── Cart (mirrors cuenta_items rows) ─────────────────────────────────────
    // Each item: ['cuenta_item_id', 'type', 'item_id', 'name',
    //             'unit_price', 'base_price', 'quantity',
    //             'tax_rate', 'tax_amount', 'subtotal']
    public array $cart = [];

    // ─── Catalog (right panel) ────────────────────────────────────────────────
    public string $productSearch = '';
    public $selectedCategoryId = null;

    // ─── Payment modal ────────────────────────────────────────────────────────
    public bool $showPaymentModal = false;
    public array $payments = [];
    public string $paymentNotes = '';

    // ─── Cancel confirm ───────────────────────────────────────────────────────
    public bool $showCancelConfirm = false;

    // ─── Persons counter ──────────────────────────────────────────────────────
    public int $numPersons = 1;

    // ─── Per-item notes modal ─────────────────────────────────────────────────
    public bool $showNotesModal = false;
    public ?int $notesItemIdx = null;
    public string $notesText = '';

    // ─── Cambio de mesa modal ─────────────────────────────────────────────────
    public bool $showChangeMesaModal = false;
    public ?int $targetMesaId = null;

    // ─── División de cuenta modal ─────────────────────────────────────────────
    public bool $showSplitModal = false;
    public array $splitQty = []; // [cuenta_item_id => qty_to_pay]
    public array $splitPayments = [['method_id' => null, 'amount' => 0]];
    public string $splitNotes = '';

    // ─── Branch / Cash register ───────────────────────────────────────────────
    public $branchId = null;
    public $cashRegister = null;
    public $openReconciliation = null;
    public bool $needsReconciliation = false;

    // ─── Preparation stations (per-branch toggle) ─────────────────────────────
    public bool $useStations = false;

    // ─── Ingredient group selection modal ──────────────────────────────────────
    public bool $showIngredientGroupModal = false;
    public ?int $pendingProductId = null;
    public array $ingredientGroupsData = [];       // [{id, name, ingredients: [{id, name, stock, manage_inventory}]}]
    public array $selectedGroupIngredients = [];    // [group_id => ingredient_id]

    // ─── Lifecycle ────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $user = auth()->user();
        $this->branchId = $user->branch_id;

        $this->cashRegister = CashRegister::where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($this->cashRegister) {
            $this->branchId = $this->cashRegister->branch_id;
            $this->openReconciliation = CashReconciliation::getOpenReconciliation($this->cashRegister->id);
            $this->needsReconciliation = !$this->openReconciliation;
        } else {
            $this->needsReconciliation = true;
        }

        // Fallback: if still no branchId, use the first active branch
        if (!$this->branchId) {
            $this->branchId = Branch::where('is_active', true)->value('id');
        }

        // Branch-level toggle: whether to use preparation stations (comandas).
        $branch = $this->branchId ? Branch::find($this->branchId) : null;
        $this->useStations = (bool) ($branch?->use_preparation_stations);
    }

    // ─── Mesa navigation ──────────────────────────────────────────────────────

    public function selectSector($sectorId): void
    {
        $this->selectedSectorId = ($this->selectedSectorId == $sectorId) ? null : $sectorId;
    }

    public function openMesa(int $mesaId): void
    {
        $mesa = Mesa::with('sector')->find($mesaId);
        if (!$mesa || !$mesa->is_active) {
            $this->dispatch('notify', message: 'Mesa no disponible', type: 'error');
            return;
        }

        $this->selectedMesaId   = $mesa->id;
        $this->selectedMesaName = $mesa->name;
        $this->selectedSectorName = $mesa->sector?->name ?? '';

        // Only load an existing open cuenta here. A new cuenta is NOT created yet —
        // we wait until the user actually adds the first item so that just opening
        // and leaving a mesa doesn't flip its status to "ocupada".
        $cuenta = Cuenta::where('mesa_id', $mesa->id)->where('status', 'abierta')->first();

        if ($cuenta) {
            $this->currentCuentaId = $cuenta->id;
            $this->numPersons      = $cuenta->num_persons ?? 1;
            $this->loadCartFromCuenta($cuenta);
        } else {
            $this->currentCuentaId = null;
            $this->numPersons      = 1;
            $this->cart            = [];
        }

        $this->productSearch      = '';
        $this->selectedCategoryId = null;
        $this->view               = 'orden';
    }

    /**
     * Lazily create the cuenta + mark the mesa occupied the first time the user
     * adds an item. Returns the cuenta id.
     */
    private function ensureCuenta(): int
    {
        if ($this->currentCuentaId) {
            return $this->currentCuentaId;
        }

        $cuenta = Cuenta::create([
            'mesa_id'   => $this->selectedMesaId,
            'user_id'   => auth()->id(),
            'branch_id' => $this->branchId,
            'status'    => 'abierta',
        ]);

        Mesa::where('id', $this->selectedMesaId)->update(['status' => 'ocupada']);

        $this->currentCuentaId = $cuenta->id;
        return $cuenta->id;
    }

    public function backToMesas(): void
    {
        // If the user opened a mesa but never added anything (cuenta empty),
        // delete the cuenta and free the mesa so it doesn't stay "ocupada".
        if ($this->currentCuentaId) {
            $itemCount = CuentaItem::where('cuenta_id', $this->currentCuentaId)->count();
            if ($itemCount === 0) {
                Cuenta::where('id', $this->currentCuentaId)->delete();
                if ($this->selectedMesaId) {
                    Mesa::where('id', $this->selectedMesaId)->update(['status' => 'libre']);
                }
            }
        }

        $this->view             = 'mesas';
        $this->selectedMesaId   = null;
        $this->selectedMesaName = '';
        $this->selectedSectorName = '';
        $this->currentCuentaId  = null;
        $this->cart             = [];
        $this->numPersons       = 1;
        $this->productSearch    = '';
        $this->selectedCategoryId = null;
        $this->showPaymentModal   = false;
        $this->showCancelConfirm  = false;
        $this->showNotesModal     = false;
        $this->showChangeMesaModal = false;
        $this->showSplitModal     = false;
        $this->payments           = [];
        $this->paymentNotes       = '';
        $this->splitQty           = [];
        $this->splitPayments      = [['method_id' => null, 'amount' => 0]];
        $this->splitNotes         = '';
    }

    private function loadCartFromCuenta(Cuenta $cuenta): void
    {
        $this->cart = [];
        foreach ($cuenta->items()->with(['preparationStation', 'selectedIngredients.ingredient', 'selectedIngredients.ingredientGroup'])->orderBy('id')->get() as $item) {
            $ps = $item->preparationStation;

            // Build selected ingredients summary
            $selections = [];
            foreach ($item->selectedIngredients as $sel) {
                $selections[] = [
                    'group_id'        => $sel->ingredient_group_id,
                    'group_name'      => $sel->ingredientGroup?->name ?? '',
                    'ingredient_id'   => $sel->ingredient_id,
                    'ingredient_name' => $sel->ingredient?->name ?? '',
                ];
            }

            $this->cart[] = [
                'cuenta_item_id'       => $item->id,
                'type'                 => $item->ingredient_id ? 'ingredient' : 'product',
                'item_id'              => $item->ingredient_id ?? $item->product_id,
                'name'                 => $item->item_name,
                'unit_price'           => (float) $item->unit_price,
                'base_price'           => (float) $item->unit_price,
                'quantity'             => (float) $item->quantity,
                'tax_rate'             => (float) $item->tax_rate,
                'tax_amount'           => (float) $item->tax_amount,
                'subtotal'             => (float) $item->subtotal,
                'notes'                => $item->notes ?? '',
                'preparation_station_id' => $item->preparation_station_id,
                'station_name'         => $ps?->name,
                'station_icon'         => $ps?->icon,
                'station_color'        => $ps?->color,
                'sent_at'              => $item->sent_at?->toDateTimeString(),
                'selected_ingredients' => $selections,
            ];
        }
    }

    // ─── Cart management ──────────────────────────────────────────────────────

    public function addProductToCart(int $productId): void
    {
        $product = Product::with(['tax', 'ingredients', 'ingredientGroups.ingredients'])->find($productId);
        if (!$product) return;

        // If the product has ingredient groups → open the selection modal
        if ($product->ingredientGroups->count() > 0) {
            $this->openIngredientGroupModal($productId);
            return;
        }

        // No groups → add directly (existing behavior)
        $this->addProductToCartDirect($productId, $product);
    }

    /**
     * Open the ingredient-group selection modal for a product.
     */
    public function openIngredientGroupModal(int $productId): void
    {
        $product = Product::with(['ingredientGroups.ingredients'])->find($productId);
        if (!$product) return;

        $this->pendingProductId = $productId;
        $this->ingredientGroupsData = [];
        $this->selectedGroupIngredients = [];

        foreach ($product->ingredientGroups as $group) {
            $ingredients = [];
            foreach ($group->ingredients()->where('is_active', true)->orderBy('name')->get() as $ing) {
                $ingredients[] = [
                    'id'               => $ing->id,
                    'name'             => $ing->name,
                    'stock'            => (float) $ing->stock,
                    'manage_inventory' => (bool) $ing->manage_inventory,
                ];
            }
            $this->ingredientGroupsData[] = [
                'id'          => $group->id,
                'name'        => $group->name,
                'ingredients' => $ingredients,
            ];
            // Pre-select the first ingredient if available
            if (!empty($ingredients)) {
                $this->selectedGroupIngredients[$group->id] = $ingredients[0]['id'];
            }
        }

        $this->showIngredientGroupModal = true;
    }

    /**
     * Confirm ingredient selections and add the product to the cart.
     */
    public function confirmIngredientSelection(): void
    {
        if (!$this->pendingProductId) return;

        $product = Product::with(['tax', 'ingredients', 'ingredientGroups', 'preparationStation'])->find($this->pendingProductId);
        if (!$product) return;

        // Validate all groups have a selection
        foreach ($this->ingredientGroupsData as $group) {
            if (empty($this->selectedGroupIngredients[$group['id']])) {
                $this->dispatch('notify', message: "Selecciona una opción en \"{$group['name']}\"", type: 'error');
                return;
            }
        }

        // Check stock of selected ingredients
        foreach ($this->ingredientGroupsData as $group) {
            $selectedId = $this->selectedGroupIngredients[$group['id']];
            $ingredient = Ingredient::find($selectedId);
            if ($ingredient && $ingredient->manage_inventory && $ingredient->stock < 1) {
                $this->dispatch('notify', message: "Sin stock para \"{$ingredient->name}\"", type: 'error');
                return;
            }
        }

        // Add the product to the cart (always as a new row since selections vary)
        $this->addProductToCartDirect($this->pendingProductId, $product, $this->selectedGroupIngredients);

        $this->closeIngredientGroupModal();
    }

    public function closeIngredientGroupModal(): void
    {
        $this->showIngredientGroupModal = false;
        $this->pendingProductId = null;
        $this->ingredientGroupsData = [];
        $this->selectedGroupIngredients = [];
    }

    /**
     * Internal: add a product to the cart, optionally with ingredient group selections.
     * When $groupSelections is provided, a new cart row is always created (never merged).
     */
    private function addProductToCartDirect(int $productId, Product $product, array $groupSelections = []): void
    {
        // Stock check for managed-inventory products
        if ($product->manages_inventory && $product->current_stock <= 0) {
            $this->dispatch('notify', message: "Sin stock disponible para \"{$product->name}\"", type: 'error');
            return;
        }

        // Ingredient-level stock check for "compuesto" products (recipe ingredients)
        if ($product->product_type === 'compuesto') {
            $currentInCart = 0.0;
            foreach ($this->cart as $row) {
                if (!empty($row['sent_at'])) continue;
                if ($row['type'] === 'product' && $row['item_id'] == $productId) {
                    $currentInCart += (float) $row['quantity'];
                }
            }
            $neededQty = $currentInCart + 1;

            foreach ($product->ingredients as $ingredient) {
                if (!$ingredient->manage_inventory) continue;
                $needed = (float) $ingredient->pivot->quantity * $neededQty;
                if ((float) $ingredient->stock < $needed) {
                    $this->dispatch('notify',
                        message: "Sin ingrediente \"{$ingredient->name}\" suficiente para preparar \"{$product->name}\".",
                        type: 'error'
                    );
                    return;
                }
            }
        }

        // Lazily create the cuenta + mark mesa ocupada on first item
        $this->ensureCuenta();

        // Price calculation (same logic as POS)
        $taxRate = 0.0;
        $basePrice = (float) $product->sale_price;

        if ($product->tax && $product->price_includes_tax) {
            $taxRate = (float) $product->tax->percentage / 100;
            $basePrice = round($basePrice / (1 + $taxRate), 6);
        } elseif ($product->tax && !$product->price_includes_tax) {
            $taxRate = (float) $product->tax->percentage / 100;
        }

        // Products WITH group selections always get a new row (can't merge)
        $hasGroups = !empty($groupSelections);

        if (!$hasGroups) {
            $cartKey = 'p-' . $productId;
            $idx = $this->findCartIndex($cartKey);

            if ($idx !== null) {
                $newQty = $this->cart[$idx]['quantity'] + 1;

                if ($product->manages_inventory && $newQty > $product->current_stock) {
                    $this->dispatch('notify', message: "Stock insuficiente para \"{$product->name}\"", type: 'error');
                    return;
                }

                $taxAmt = round($basePrice * $taxRate * $newQty, 2);
                $subtotal = round($basePrice * $newQty, 2);

                $this->cart[$idx]['quantity']  = $newQty;
                $this->cart[$idx]['tax_amount'] = $taxAmt;
                $this->cart[$idx]['subtotal']  = $subtotal;

                CuentaItem::where('id', $this->cart[$idx]['cuenta_item_id'])->update([
                    'quantity'   => $newQty,
                    'tax_amount' => $taxAmt,
                    'subtotal'   => $subtotal,
                ]);
                return;
            }
        }

        // New row
        $taxAmt  = round($basePrice * $taxRate, 2);
        $subtotal = round($basePrice, 2);

        $product->loadMissing('preparationStation');
        $ps = $product->preparationStation;

        $ci = CuentaItem::create([
            'cuenta_id'              => $this->currentCuentaId,
            'product_id'             => $productId,
            'item_name'              => $product->name,
            'unit_price'             => $basePrice,
            'quantity'               => 1,
            'tax_rate'               => round($taxRate * 100, 2),
            'tax_amount'             => $taxAmt,
            'subtotal'               => $subtotal,
            'notes'                  => null,
            'preparation_station_id' => $product->preparation_station_id,
        ]);

        // Save selected ingredients to DB
        $selections = [];
        if ($hasGroups) {
            foreach ($groupSelections as $groupId => $ingredientId) {
                CuentaItemSelectedIngredient::create([
                    'cuenta_item_id'      => $ci->id,
                    'ingredient_group_id' => $groupId,
                    'ingredient_id'       => $ingredientId,
                ]);
                $group = IngredientGroup::find($groupId);
                $ingredient = Ingredient::find($ingredientId);
                $selections[] = [
                    'group_id'        => (int) $groupId,
                    'group_name'      => $group?->name ?? '',
                    'ingredient_id'   => (int) $ingredientId,
                    'ingredient_name' => $ingredient?->name ?? '',
                ];
            }
        }

        $this->cart[] = [
            'cuenta_item_id'         => $ci->id,
            'type'                   => 'product',
            'item_id'                => $productId,
            'name'                   => $product->name,
            'unit_price'             => $basePrice,
            'base_price'             => $basePrice,
            'quantity'               => 1.0,
            'tax_rate'               => round($taxRate * 100, 2),
            'tax_amount'             => $taxAmt,
            'subtotal'               => $subtotal,
            'notes'                  => '',
            'preparation_station_id' => $product->preparation_station_id,
            'station_name'           => $ps?->name,
            'station_icon'           => $ps?->icon,
            'station_color'          => $ps?->color,
            'sent_at'                => null,
            'selected_ingredients'   => $selections,
        ];
    }

    public function addIngredientToCart(int $ingredientId): void
    {
        $ingredient = Ingredient::with('tax')->find($ingredientId);
        if (!$ingredient || !$ingredient->show_in_pos) return;

        // Lazily create the cuenta + mark mesa ocupada on first item
        $this->ensureCuenta();

        $taxRate   = 0.0;
        $basePrice = (float) $ingredient->sale_price;

        if ($ingredient->includes_tax && $ingredient->tax) {
            $taxRate   = (float) $ingredient->tax->percentage / 100;
            $basePrice = round($basePrice / (1 + $taxRate), 6);
        } elseif ($ingredient->tax) {
            $taxRate = (float) $ingredient->tax->percentage / 100;
        }

        $cartKey = 'i-' . $ingredientId;
        $idx = $this->findCartIndex($cartKey);

        if ($idx !== null) {
            $newQty  = $this->cart[$idx]['quantity'] + 1;
            $taxAmt  = round($basePrice * $taxRate * $newQty, 2);
            $subtotal = round($basePrice * $newQty, 2);

            $this->cart[$idx]['quantity']  = $newQty;
            $this->cart[$idx]['tax_amount'] = $taxAmt;
            $this->cart[$idx]['subtotal']  = $subtotal;

            CuentaItem::where('id', $this->cart[$idx]['cuenta_item_id'])->update([
                'quantity'   => $newQty,
                'tax_amount' => $taxAmt,
                'subtotal'   => $subtotal,
            ]);
        } else {
            $taxAmt  = round($basePrice * $taxRate, 2);
            $subtotal = round($basePrice, 2);

            $ingredient->loadMissing('preparationStation');
            $ps = $ingredient->preparationStation;

            $ci = CuentaItem::create([
                'cuenta_id'              => $this->currentCuentaId,
                'ingredient_id'          => $ingredientId,
                'item_name'              => $ingredient->name,
                'unit_price'             => $basePrice,
                'quantity'               => 1,
                'tax_rate'               => round($taxRate * 100, 2),
                'tax_amount'             => $taxAmt,
                'subtotal'               => $subtotal,
                'notes'                  => null,
                'preparation_station_id' => $ingredient->preparation_station_id,
            ]);

            $this->cart[] = [
                'cuenta_item_id'         => $ci->id,
                'type'                   => 'ingredient',
                'item_id'                => $ingredientId,
                'name'                   => $ingredient->name,
                'unit_price'             => $basePrice,
                'base_price'             => $basePrice,
                'quantity'               => 1.0,
                'tax_rate'               => round($taxRate * 100, 2),
                'tax_amount'             => $taxAmt,
                'subtotal'               => $subtotal,
                'notes'                  => '',
                'preparation_station_id' => $ingredient->preparation_station_id,
                'station_name'           => $ps?->name,
                'station_icon'           => $ps?->icon,
                'station_color'          => $ps?->color,
                'sent_at'                => null,
            ];
        }
    }

    public function incrementQty(int $idx): void
    {
        if (!isset($this->cart[$idx])) return;

        $item = $this->cart[$idx];

        // Stock check if product
        if ($item['type'] === 'product') {
            $product = Product::with('ingredients')->find($item['item_id']);
            if ($product && $product->manages_inventory) {
                $currentInCart = $item['quantity'] + 1;
                if ($currentInCart > $product->current_stock) {
                    $this->dispatch('notify', message: "Stock insuficiente para \"{$item['name']}\"", type: 'error');
                    return;
                }
            }

            // Compuesto: re-check ingredient stock for the increment.
            if ($product && $product->product_type === 'compuesto') {
                // Count all unsent rows of this same product across the cart.
                $totalSameProduct = 0.0;
                foreach ($this->cart as $row) {
                    if (!empty($row['sent_at'])) continue;
                    if ($row['type'] === 'product' && $row['item_id'] == $item['item_id']) {
                        $totalSameProduct += (float) $row['quantity'];
                    }
                }
                $neededQty = $totalSameProduct + 1; // +1 because we're incrementing now

                foreach ($product->ingredients as $ingredient) {
                    if (!$ingredient->manage_inventory) continue;
                    $needed = (float) $ingredient->pivot->quantity * $neededQty;
                    if ((float) $ingredient->stock < $needed) {
                        $this->dispatch('notify',
                            message: "Sin ingrediente \"{$ingredient->name}\" suficiente para preparar otro \"{$product->name}\".",
                            type: 'error'
                        );
                        return;
                    }
                }
            }
        }

        $newQty   = $item['quantity'] + 1;
        $taxAmt   = round($item['base_price'] * ($item['tax_rate'] / 100) * $newQty, 2);
        $subtotal = round($item['base_price'] * $newQty, 2);

        $this->cart[$idx]['quantity']   = $newQty;
        $this->cart[$idx]['tax_amount'] = $taxAmt;
        $this->cart[$idx]['subtotal']   = $subtotal;

        CuentaItem::where('id', $item['cuenta_item_id'])->update([
            'quantity'   => $newQty,
            'tax_amount' => $taxAmt,
            'subtotal'   => $subtotal,
        ]);
    }

    public function decrementQty(int $idx): void
    {
        if (!isset($this->cart[$idx])) return;

        $item = $this->cart[$idx];

        if ($item['quantity'] <= 1) {
            $this->removeItem($idx);
            return;
        }

        $newQty   = $item['quantity'] - 1;
        $taxAmt   = round($item['base_price'] * ($item['tax_rate'] / 100) * $newQty, 2);
        $subtotal = round($item['base_price'] * $newQty, 2);

        $this->cart[$idx]['quantity']   = $newQty;
        $this->cart[$idx]['tax_amount'] = $taxAmt;
        $this->cart[$idx]['subtotal']   = $subtotal;

        CuentaItem::where('id', $item['cuenta_item_id'])->update([
            'quantity'   => $newQty,
            'tax_amount' => $taxAmt,
            'subtotal'   => $subtotal,
        ]);
    }

    public function removeItem(int $idx): void
    {
        if (!isset($this->cart[$idx])) return;

        CuentaItem::find($this->cart[$idx]['cuenta_item_id'])?->delete();
        array_splice($this->cart, $idx, 1);
    }

    // ─── Send to kitchen (generate comandas per preparation station) ───────────

    /**
     * Confirm the current batch of items:
     *   - Items with a preparation_station_id  → create kitchen orders grouped by station
     *   - Items without station                → just mark as sent (no kitchen order)
     *
     * Either way, after this call every cart item has sent_at set so it moves
     * out of the "Pedido actual" list and into the "Ya pedido" summary.
     */
    public function sendToKitchen(): void
    {
        if (!$this->useStations) {
            $this->dispatch('notify', message: 'Las comandas están desactivadas para esta sucursal', type: 'error');
            return;
        }

        if (!auth()->user()->hasPermission('kitchen.send')) {
            $this->dispatch('notify', message: 'No tienes permiso para hacer pedidos', type: 'error');
            return;
        }

        if (empty($this->cart) || !$this->currentCuentaId) {
            $this->dispatch('notify', message: 'No hay ítems por pedir', type: 'error');
            return;
        }

        // Collect every unsent item; group those with a station separately.
        $allUnsent = [];
        $byStation = [];
        foreach ($this->cart as $idx => $item) {
            if (!empty($item['sent_at'])) continue;
            $allUnsent[] = ['idx' => $idx, 'item' => $item];
            if (!empty($item['preparation_station_id'])) {
                $stationId = (int) $item['preparation_station_id'];
                $byStation[$stationId][] = ['idx' => $idx, 'item' => $item];
            }
        }

        if (empty($allUnsent)) {
            $this->dispatch('notify', message: 'No hay ítems nuevos por pedir', type: 'info');
            return;
        }

        try {
            DB::beginTransaction();

            $now              = now();
            $kitchenOrdersQty = 0;

            // 1) Create one KitchenOrder per station that has items
            foreach ($byStation as $stationId => $entries) {
                $order = KitchenOrder::create([
                    'number'                 => KitchenOrder::generateNumber(),
                    'branch_id'              => $this->branchId,
                    'cuenta_id'              => $this->currentCuentaId,
                    'mesa_id'                => $this->selectedMesaId,
                    'preparation_station_id' => $stationId,
                    'user_id'                => auth()->id(),
                    'status'                 => 'pending',
                    'items_count'            => count($entries),
                    'sent_at'                => $now,
                ]);

                foreach ($entries as $entry) {
                    $item = $entry['item'];

                    // Build notes including selected ingredient choices
                    $itemNotes = $item['notes'] ?: '';
                    if (!empty($item['selected_ingredients'])) {
                        $selLabels = [];
                        foreach ($item['selected_ingredients'] as $sel) {
                            $selLabels[] = $sel['group_name'] . ': ' . $sel['ingredient_name'];
                        }
                        $selText = implode(' · ', $selLabels);
                        $itemNotes = $itemNotes ? ($itemNotes . ' | ' . $selText) : $selText;
                    }

                    KitchenOrderItem::create([
                        'kitchen_order_id' => $order->id,
                        'cuenta_item_id'   => $item['cuenta_item_id'],
                        'product_id'       => $item['type'] === 'product'    ? $item['item_id'] : null,
                        'ingredient_id'    => $item['type'] === 'ingredient' ? $item['item_id'] : null,
                        'item_name'        => $item['name'],
                        'quantity'         => $item['quantity'],
                        'notes'            => $itemNotes ?: null,
                        'status'           => 'pending',
                    ]);
                }

                $kitchenOrdersQty++;
            }

            // 2) Mark every unsent cart item as sent (so the Mostrador clears them)
            $cuentaItemIds = array_map(fn($e) => $e['item']['cuenta_item_id'], $allUnsent);
            CuentaItem::whereIn('id', $cuentaItemIds)->update(['sent_at' => $now]);

            foreach ($allUnsent as $entry) {
                $this->cart[$entry['idx']]['sent_at'] = $now->toDateTimeString();
            }

            DB::commit();

            $msg = $kitchenOrdersQty > 0
                ? ($kitchenOrdersQty === 1
                    ? 'Pedido realizado — 1 comanda enviada a cocina'
                    : "Pedido realizado — {$kitchenOrdersQty} comandas enviadas")
                : 'Pedido confirmado';
            $this->dispatch('notify', message: $msg, type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al hacer pedido: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Whether there are items in the cart that have not been confirmed yet.
     */
    public function getHasUnsentItemsProperty(): bool
    {
        foreach ($this->cart as $item) {
            if (empty($item['sent_at'])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cart rows not yet confirmed (shown in the main "Pedido actual" list).
     * Each entry keeps its original index so that the existing wire:click
     * handlers (removeItem, incrementQty, openNotesModal, …) keep working.
     */
    public function getUnsentItemsProperty(): array
    {
        $out = [];
        foreach ($this->cart as $idx => $item) {
            if (empty($item['sent_at'])) {
                $out[] = ['idx' => $idx, 'item' => $item];
            }
        }
        return $out;
    }

    /**
     * Cart rows already confirmed (shown in the collapsible "Ya pedido" summary).
     */
    public function getSentItemsProperty(): array
    {
        $out = [];
        foreach ($this->cart as $idx => $item) {
            if (!empty($item['sent_at'])) {
                $out[] = ['idx' => $idx, 'item' => $item];
            }
        }
        return $out;
    }

    /**
     * Subtotal of unsent items only (current batch) — used in the CTA preview.
     */
    public function getPendingBatchTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->cart as $item) {
            if (empty($item['sent_at'])) {
                $total += (float) $item['subtotal'] + (float) $item['tax_amount'];
            }
        }
        return round($total, 2);
    }

    // ─── Cart helpers ─────────────────────────────────────────────────────────

    private function findCartIndex(string $cartKey): ?int
    {
        [$type, $id] = explode('-', $cartKey, 2);
        $itemType = $type === 'p' ? 'product' : 'ingredient';

        foreach ($this->cart as $k => $item) {
            // Skip items already confirmed in a previous "Hacer pedido" batch —
            // we must keep them frozen and start a new row for the current batch.
            if (!empty($item['sent_at'])) continue;

            if ($item['type'] === $itemType && $item['item_id'] == $id) {
                return $k;
            }
        }
        return null;
    }

    public function getSubtotalProperty(): float
    {
        return round(array_sum(array_column($this->cart, 'subtotal')), 2);
    }

    public function getTaxTotalProperty(): float
    {
        return round(array_sum(array_column($this->cart, 'tax_amount')), 2);
    }

    public function getTotalProperty(): float
    {
        return round($this->getSubtotalProperty() + $this->getTaxTotalProperty(), 2);
    }

    public function getTotalReceivedProperty(): float
    {
        return round(array_sum(array_column($this->payments, 'amount')), 2);
    }

    public function getPendingAmountProperty(): float
    {
        return max(0, round($this->getTotalProperty() - $this->getTotalReceivedProperty(), 2));
    }

    public function getChangeProperty(): float
    {
        return max(0, round($this->getTotalReceivedProperty() - $this->getTotalProperty(), 2));
    }

    // ─── Payment ──────────────────────────────────────────────────────────────

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'La orden está vacía', type: 'error');
            return;
        }

        if ($this->needsReconciliation) {
            $this->dispatch('notify', message: 'Debes abrir la caja antes de cobrar', type: 'error');
            return;
        }

        // Init single payment slot with full total
        $this->payments = [['method_id' => null, 'amount' => $this->getTotalProperty()]];
        $this->paymentNotes = '';
        $this->showPaymentModal = true;
    }

    public function addPaymentMethod(): void
    {
        $this->payments[] = ['method_id' => null, 'amount' => 0];
    }

    public function removePaymentMethod(int $index): void
    {
        array_splice($this->payments, $index, 1);
    }

    public function closePaymentModal(): void
    {
        $this->showPaymentModal = false;
        $this->payments = [];
    }

    public function processPayment(): void
    {
        // Validate payments
        foreach ($this->payments as $payment) {
            if (empty($payment['method_id'])) {
                $this->dispatch('notify', message: 'Selecciona un método de pago', type: 'error');
                return;
            }
        }

        if ($this->getPendingAmountProperty() > 0) {
            $this->dispatch('notify', message: 'El monto recibido es insuficiente', type: 'error');
            return;
        }

        try {
            DB::beginTransaction();

            $total    = $this->getTotalProperty();
            $subtotal = $this->getSubtotalProperty();
            $taxTotal = $this->getTaxTotalProperty();

            // Resolve waiter (user who opened the cuenta) — distinct from the cashier.
            $waiterId = Cuenta::where('id', $this->currentCuentaId)->value('user_id');

            // Create Sale
            $sale = Sale::create([
                'branch_id'               => $this->branchId,
                'cash_reconciliation_id'  => $this->openReconciliation->id,
                'mesa_id'                 => $this->selectedMesaId,
                'user_id'                 => auth()->id(),
                'waiter_id'               => $waiterId,
                'invoice_number'          => Sale::generateInvoiceNumber($this->branchId),
                'subtotal'                => $subtotal,
                'tax_total'               => $taxTotal,
                'discount'                => 0,
                'total'                   => $total,
                'status'                  => 'completed',
                'payment_type'            => 'cash',
                'payment_status'          => 'paid',
                'credit_amount'           => 0,
                'paid_amount'             => $total,
                'notes'                   => $this->paymentNotes ?: null,
                'source'                  => 'pos',
            ]);

            // Create sale items and handle stock
            foreach ($this->cart as $item) {
                $productId    = $item['type'] === 'product'    ? $item['item_id'] : null;
                $ingredientId = $item['type'] === 'ingredient' ? $item['item_id'] : null;

                SaleItem::create([
                    'sale_id'      => $sale->id,
                    'product_id'   => $productId,
                    'product_name' => $item['name'],
                    'product_sku'  => null,
                    'unit_price'   => $item['base_price'],
                    'quantity'     => $item['quantity'],
                    'tax_rate'     => $item['tax_rate'],
                    'tax_amount'   => $item['tax_amount'],
                    'subtotal'     => $item['subtotal'],
                    'discount_type_value' => 0,
                    'discount_amount'     => 0,
                    'total'        => $item['subtotal'] + $item['tax_amount'],
                ]);

                // Product stock
                if ($productId) {
                    $product = Product::find($productId);
                    if ($product && $product->manages_inventory) {
                        InventoryMovement::createMovement(
                            'sale', $product, 'out', $item['quantity'],
                            (float) $item['base_price'],
                            "Venta #{$sale->invoice_number} (Mostrador: {$this->selectedMesaName})",
                            $sale, $this->branchId
                        );
                        $product->decrement('current_stock', $item['quantity']);
                    }

                    // Compuesto: deduct recipe ingredients
                    if ($product && $product->product_type === 'compuesto') {
                        $productWithIng = Product::with('ingredients')->find($productId);
                        foreach ($productWithIng->ingredients as $ingredient) {
                            if ($ingredient->manage_inventory) {
                                $ingredient->decrement('stock', (float) $ingredient->pivot->quantity * (float) $item['quantity']);
                            }
                        }
                    }

                    // Deduct selected group ingredients (elegibles)
                    if (!empty($item['selected_ingredients'])) {
                        foreach ($item['selected_ingredients'] as $sel) {
                            $selIngredient = Ingredient::find($sel['ingredient_id']);
                            if ($selIngredient && $selIngredient->manage_inventory) {
                                $selIngredient->decrement('stock', (float) $item['quantity']);
                            }
                        }
                    }
                }

                // Ingredient stock (sold as individual item)
                if ($ingredientId) {
                    $ingredient = Ingredient::find($ingredientId);
                    if ($ingredient && $ingredient->manage_inventory) {
                        $ingredient->decrement('stock', $item['quantity']);
                    }
                }
            }

            // Sale payments (adjust last to match exact total)
            $validPayments = array_values(array_filter($this->payments, fn($p) => (float)($p['amount'] ?? 0) > 0 && !empty($p['method_id'])));
            $paid = 0;
            foreach ($validPayments as $i => $payment) {
                $isLast  = ($i === count($validPayments) - 1);
                $amount  = $isLast ? round($total - $paid, 2) : min((float) $payment['amount'], round($total - $paid, 2));
                if ($amount > 0) {
                    SalePayment::create([
                        'sale_id'           => $sale->id,
                        'payment_method_id' => $payment['method_id'],
                        'amount'            => $amount,
                    ]);
                    $paid += $amount;
                }
            }

            // Close cuenta and free mesa
            Cuenta::where('id', $this->currentCuentaId)->update([
                'status'  => 'cerrada',
                'sale_id' => $sale->id,
            ]);
            Mesa::where('id', $this->selectedMesaId)->update(['status' => 'libre']);

            // Mark any remaining kitchen orders of this cuenta as delivered
            KitchenOrder::where('cuenta_id', $this->currentCuentaId)
                ->whereIn('status', ['pending', 'preparing', 'ready'])
                ->update([
                    'status'       => 'delivered',
                    'delivered_at' => now(),
                ]);

            DB::commit();

            ActivityLogService::logCreate('mostrador', $sale, "Venta desde Mostrador, Mesa: {$this->selectedMesaName}");
            $this->dispatch('notify', message: "Venta procesada correctamente — {$sale->invoice_number}", type: 'success');

            // Open the receipt window (mirror of POS behavior)
            $this->dispatch('print-receipt', saleId: $sale->id);

            $this->backToMesas();
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al procesar el pago: ' . $e->getMessage(), type: 'error');
        }
    }

    // ─── Cancel cuenta ────────────────────────────────────────────────────────

    public function confirmCancelarCuenta(): void
    {
        $this->showCancelConfirm = true;
    }

    public function cancelarCuenta(): void
    {
        if ($this->currentCuentaId) {
            Cuenta::where('id', $this->currentCuentaId)->update(['status' => 'cancelada']);
            Mesa::where('id', $this->selectedMesaId)->update(['status' => 'libre']);

            // Cancel pending kitchen orders for this cuenta
            KitchenOrder::where('cuenta_id', $this->currentCuentaId)
                ->whereIn('status', ['pending', 'preparing', 'ready'])
                ->update(['status' => 'cancelled']);
        }
        $this->dispatch('notify', message: 'Cuenta cancelada', type: 'success');
        $this->backToMesas();
    }

    // ─── Category filter ──────────────────────────────────────────────────────

    public function selectCategory($categoryId): void
    {
        $this->selectedCategoryId = ($this->selectedCategoryId == $categoryId) ? null : $categoryId;
    }

    // ─── Persons counter ──────────────────────────────────────────────────────

    public function incrementPersons(): void
    {
        if ($this->numPersons < 99) {
            $this->numPersons++;
            if ($this->currentCuentaId) {
                Cuenta::where('id', $this->currentCuentaId)->update(['num_persons' => $this->numPersons]);
            }
        }
    }

    public function decrementPersons(): void
    {
        if ($this->numPersons > 1) {
            $this->numPersons--;
            if ($this->currentCuentaId) {
                Cuenta::where('id', $this->currentCuentaId)->update(['num_persons' => $this->numPersons]);
            }
        }
    }

    // ─── Per-item notes ───────────────────────────────────────────────────────

    public function openNotesModal(int $idx): void
    {
        if (!isset($this->cart[$idx])) return;
        $this->notesItemIdx = $idx;
        $this->notesText    = $this->cart[$idx]['notes'] ?? '';
        $this->showNotesModal = true;
    }

    public function saveNotes(): void
    {
        $idx = $this->notesItemIdx;
        if ($idx === null || !isset($this->cart[$idx])) {
            $this->showNotesModal = false;
            return;
        }
        $this->cart[$idx]['notes'] = $this->notesText;
        CuentaItem::where('id', $this->cart[$idx]['cuenta_item_id'])
            ->update(['notes' => $this->notesText ?: null]);
        $this->showNotesModal = false;
        $this->notesItemIdx   = null;
        $this->notesText      = '';
    }

    public function closeNotesModal(): void
    {
        $this->showNotesModal = false;
        $this->notesItemIdx   = null;
        $this->notesText      = '';
    }

    // ─── Cambio de mesa ───────────────────────────────────────────────────────

    public function openChangeMesaModal(): void
    {
        $this->targetMesaId = null;
        $this->showChangeMesaModal = true;
    }

    public function confirmChangeMesa(): void
    {
        if (!$this->targetMesaId) {
            $this->dispatch('notify', message: 'Selecciona una mesa destino', type: 'error');
            return;
        }

        $targetMesa = Mesa::find($this->targetMesaId);
        if (!$targetMesa || !$targetMesa->is_active || $targetMesa->status !== 'libre') {
            $this->dispatch('notify', message: 'La mesa destino no está disponible', type: 'error');
            return;
        }

        DB::transaction(function () use ($targetMesa) {
            // Move cuenta to new mesa
            Cuenta::where('id', $this->currentCuentaId)->update(['mesa_id' => $targetMesa->id]);
            // Free old mesa
            Mesa::where('id', $this->selectedMesaId)->update(['status' => 'libre']);
            // Occupy new mesa
            $targetMesa->update(['status' => 'ocupada']);
        });

        $oldName = $this->selectedMesaName;
        $this->selectedMesaId   = $targetMesa->id;
        $this->selectedMesaName = $targetMesa->name;
        $this->selectedSectorName = $targetMesa->sector?->name ?? $this->selectedSectorName;
        $this->showChangeMesaModal = false;
        $this->targetMesaId = null;
        $this->dispatch('notify', message: "Mesa cambiada de {$oldName} a {$targetMesa->name}", type: 'success');
    }

    public function closeChangeMesaModal(): void
    {
        $this->showChangeMesaModal = false;
        $this->targetMesaId = null;
    }

    // ─── Precuenta (print preview) ────────────────────────────────────────────

    public function openPrecuenta(): void
    {
        if (!$this->currentCuentaId) return;
        $this->dispatch('openWindow', url: route('mostrador.precuenta', $this->currentCuentaId));
    }

    // ─── Comandas ─────────────────────────────────────────────────────────────

    public function openComanda(): void
    {
        if (!$this->currentCuentaId) return;
        $this->dispatch('openWindow', url: route('mostrador.comanda', $this->currentCuentaId));
    }

    // ─── División de cuenta ───────────────────────────────────────────────────

    public function openSplitModal(): void
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', message: 'La orden está vacía', type: 'error');
            return;
        }
        // Initialize split qty to 0 for each item
        $this->splitQty = [];
        foreach ($this->cart as $item) {
            $this->splitQty[$item['cuenta_item_id']] = 0;
        }
        $this->splitPayments = [['method_id' => null, 'amount' => 0]];
        $this->splitNotes    = '';
        $this->showSplitModal = true;
    }

    public function getSplitTotalProperty(): float
    {
        $total = 0.0;
        foreach ($this->cart as $item) {
            $qtyToPay = (float) ($this->splitQty[$item['cuenta_item_id']] ?? 0);
            if ($qtyToPay <= 0) continue;
            $lineSubtotal = round($item['base_price'] * $qtyToPay, 2);
            $lineTax      = round($item['base_price'] * ($item['tax_rate'] / 100) * $qtyToPay, 2);
            $total += $lineSubtotal + $lineTax;
        }
        return round($total, 2);
    }

    public function addSplitPaymentMethod(): void
    {
        $this->splitPayments[] = ['method_id' => null, 'amount' => 0];
    }

    public function removeSplitPaymentMethod(int $index): void
    {
        array_splice($this->splitPayments, $index, 1);
    }

    public function processSplitPayment(): void
    {
        if ($this->needsReconciliation) {
            $this->dispatch('notify', message: 'Debes abrir la caja antes de cobrar', type: 'error');
            return;
        }

        $splitTotal = $this->getSplitTotalProperty();

        if ($splitTotal <= 0) {
            $this->dispatch('notify', message: 'Selecciona al menos un ítem para cobrar', type: 'error');
            return;
        }

        foreach ($this->splitPayments as $p) {
            if (empty($p['method_id'])) {
                $this->dispatch('notify', message: 'Selecciona un método de pago', type: 'error');
                return;
            }
        }

        $splitReceived = round(array_sum(array_column($this->splitPayments, 'amount')), 2);
        if ($splitReceived < $splitTotal) {
            $this->dispatch('notify', message: 'El monto recibido es insuficiente', type: 'error');
            return;
        }

        try {
            DB::beginTransaction();

            // Collect items being paid
            $paidItems = [];
            $subtotalAcc = 0.0;
            $taxAcc      = 0.0;

            foreach ($this->cart as $item) {
                $qtyToPay = (float) ($this->splitQty[$item['cuenta_item_id']] ?? 0);
                if ($qtyToPay <= 0) continue;
                $lineSubtotal = round($item['base_price'] * $qtyToPay, 2);
                $lineTax      = round($item['base_price'] * ($item['tax_rate'] / 100) * $qtyToPay, 2);
                $subtotalAcc += $lineSubtotal;
                $taxAcc      += $lineTax;
                $paidItems[]  = [
                    'cart_item'  => $item,
                    'qty_paid'   => $qtyToPay,
                    'subtotal'   => $lineSubtotal,
                    'tax_amount' => $lineTax,
                ];
            }

            $subtotalAcc = round($subtotalAcc, 2);
            $taxAcc      = round($taxAcc, 2);
            $totalSplit  = round($subtotalAcc + $taxAcc, 2);

            // Resolve waiter (user who opened the cuenta) — distinct from the cashier.
            $waiterId = Cuenta::where('id', $this->currentCuentaId)->value('user_id');

            // Create partial sale
            $sale = Sale::create([
                'branch_id'               => $this->branchId,
                'cash_reconciliation_id'  => $this->openReconciliation->id,
                'mesa_id'                 => $this->selectedMesaId,
                'user_id'                 => auth()->id(),
                'waiter_id'               => $waiterId,
                'invoice_number'          => Sale::generateInvoiceNumber($this->branchId),
                'subtotal'                => $subtotalAcc,
                'tax_total'               => $taxAcc,
                'discount'                => 0,
                'total'                   => $totalSplit,
                'status'                  => 'completed',
                'payment_type'            => 'cash',
                'payment_status'          => 'paid',
                'credit_amount'           => 0,
                'paid_amount'             => $totalSplit,
                'notes'                   => $this->splitNotes ?: null,
                'source'                  => 'pos',
            ]);

            foreach ($paidItems as $pi) {
                $item = $pi['cart_item'];
                SaleItem::create([
                    'sale_id'             => $sale->id,
                    'product_id'          => $item['type'] === 'product' ? $item['item_id'] : null,
                    'product_name'        => $item['name'],
                    'product_sku'         => null,
                    'unit_price'          => $item['base_price'],
                    'quantity'            => $pi['qty_paid'],
                    'tax_rate'            => $item['tax_rate'],
                    'tax_amount'          => $pi['tax_amount'],
                    'subtotal'            => $pi['subtotal'],
                    'discount_type_value' => 0,
                    'discount_amount'     => 0,
                    'total'               => $pi['subtotal'] + $pi['tax_amount'],
                ]);
            }

            // Record payments
            $paid = 0;
            $validPayments = array_values(array_filter($this->splitPayments, fn($p) => (float)($p['amount'] ?? 0) > 0 && !empty($p['method_id'])));
            foreach ($validPayments as $i => $payment) {
                $isLast = ($i === count($validPayments) - 1);
                $amount = $isLast ? round($totalSplit - $paid, 2) : min((float) $payment['amount'], round($totalSplit - $paid, 2));
                if ($amount > 0) {
                    SalePayment::create([
                        'sale_id'           => $sale->id,
                        'payment_method_id' => $payment['method_id'],
                        'amount'            => $amount,
                    ]);
                    $paid += $amount;
                }
            }

            // Update or remove cuenta_items
            foreach ($paidItems as $pi) {
                $item     = $pi['cart_item'];
                $qtyLeft  = $item['quantity'] - $pi['qty_paid'];

                if ($qtyLeft <= 0) {
                    CuentaItem::where('id', $item['cuenta_item_id'])->delete();
                } else {
                    $newSub = round($item['base_price'] * $qtyLeft, 2);
                    $newTax = round($item['base_price'] * ($item['tax_rate'] / 100) * $qtyLeft, 2);
                    CuentaItem::where('id', $item['cuenta_item_id'])->update([
                        'quantity'   => $qtyLeft,
                        'tax_amount' => $newTax,
                        'subtotal'   => $newSub,
                    ]);
                }
            }

            DB::commit();

            // Check if cuenta is now empty
            $remainingItems = CuentaItem::where('cuenta_id', $this->currentCuentaId)->count();

            if ($remainingItems === 0) {
                Cuenta::where('id', $this->currentCuentaId)->update(['status' => 'cerrada', 'sale_id' => $sale->id]);
                Mesa::where('id', $this->selectedMesaId)->update(['status' => 'libre']);
                $this->dispatch('notify', message: "Cuenta cerrada — {$sale->invoice_number}", type: 'success');
                $this->dispatch('print-receipt', saleId: $sale->id);
                $this->showSplitModal = false;
                $this->backToMesas();
            } else {
                // Reload cart from DB
                $cuenta = Cuenta::find($this->currentCuentaId);
                $this->loadCartFromCuenta($cuenta);
                $this->showSplitModal = false;
                $this->splitQty = [];
                $this->splitPayments = [['method_id' => null, 'amount' => 0]];
                $this->dispatch('notify', message: "Pago parcial registrado — {$sale->invoice_number}", type: 'success');
                $this->dispatch('print-receipt', saleId: $sale->id);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('notify', message: 'Error al procesar el pago parcial: ' . $e->getMessage(), type: 'error');
        }
    }

    public function closeSplitModal(): void
    {
        $this->showSplitModal = false;
        $this->splitQty = [];
        $this->splitPayments = [['method_id' => null, 'amount' => 0]];
        $this->splitNotes = '';
    }

    // ─── Render ───────────────────────────────────────────────────────────────

    public function render()
    {
        $sectors  = Sector::where('is_active', true)->orderBy('name')->get();
        $mesas    = collect();
        $sellableItems = collect();
        $categories    = collect();
        $paymentMethods = collect();

        // ── Mesa grid ────────────────────────────────────────────────────────
        $mesasQuery = Mesa::with(['sector', 'cuenta.items'])
            ->where('is_active', true)
            ->orderBy('name');

        if ($this->selectedSectorId) {
            $mesasQuery->where('sector_id', $this->selectedSectorId);
        }

        $mesas = $mesasQuery->get();

        // ── Catalog (only when in 'orden' view) ───────────────────────────
        if ($this->view === 'orden') {
            $categories = Category::where('is_active', true)->orderBy('name')->get();

            // Products
            $productsQuery = Product::with(['tax', 'unit', 'ingredientGroups'])
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->where('manages_inventory', false)
                      ->orWhere('current_stock', '>', 0);
                })
                ->forBranch($this->branchId);

            if ($this->selectedCategoryId) {
                $productsQuery->where('category_id', $this->selectedCategoryId);
            }

            if (strlen(trim($this->productSearch)) >= 2) {
                $search = trim($this->productSearch);
                $productsQuery->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $products = $productsQuery->orderBy('name')->limit(60)->get();

            foreach ($products as $product) {
                $taxRate = 0.0;
                if ($product->tax) {
                    $taxRate = (float) $product->tax->percentage / 100;
                }
                $displayPrice = $product->price_includes_tax
                    ? (float) $product->sale_price
                    : round((float) $product->sale_price * (1 + $taxRate), 2);

                $sellableItems->push([
                    'type'             => 'product',
                    'id'               => $product->id,
                    'name'             => $product->name,
                    'price'            => $displayPrice,
                    'image'            => $product->image,
                    'unit'             => $product->unit?->abbreviation ?? 'UND',
                    'manages_inventory' => (bool) $product->manages_inventory,
                    'stock'            => (float) $product->current_stock,
                    'has_groups'       => $product->ingredientGroups->count() > 0,
                ]);
            }

            // Ingredients with show_in_pos = true and sale_price > 0
            $ingredientsQuery = Ingredient::with('tax')
                ->where('is_active', true)
                ->where('show_in_pos', true)
                ->where('sale_price', '>', 0);

            if (strlen(trim($this->productSearch)) >= 2) {
                $search = trim($this->productSearch);
                $ingredientsQuery->where('name', 'like', "%{$search}%");
            }

            $ingredients = $ingredientsQuery->orderBy('name')->limit(30)->get();

            foreach ($ingredients as $ingredient) {
                $taxRate = 0.0;
                if ($ingredient->includes_tax && $ingredient->tax) {
                    $taxRate = (float) $ingredient->tax->percentage / 100;
                } elseif ($ingredient->tax) {
                    $taxRate = (float) $ingredient->tax->percentage / 100;
                }
                $displayPrice = $ingredient->includes_tax
                    ? (float) $ingredient->sale_price
                    : round((float) $ingredient->sale_price * (1 + $taxRate), 2);

                $sellableItems->push([
                    'type'  => 'ingredient',
                    'id'    => $ingredient->id,
                    'name'  => $ingredient->name,
                    'price' => $displayPrice,
                    'image' => null,
                    'unit'  => 'UND',
                    'manages_inventory' => false,
                    'stock' => null,
                ]);
            }

            $paymentMethods = PaymentMethod::where('is_active', true)->get();
        }

        return view('livewire.mostrador', [
            'sectors'        => $sectors,
            'mesas'          => $mesas,
            'categories'     => $categories,
            'sellableItems'  => $sellableItems,
            'paymentMethods' => $paymentMethods,
            'subtotal'       => $this->getSubtotalProperty(),
            'taxTotal'       => $this->getTaxTotalProperty(),
            'total'          => $this->getTotalProperty(),
            'totalReceived'  => $this->getTotalReceivedProperty(),
            'pendingAmount'  => $this->getPendingAmountProperty(),
            'change'         => $this->getChangeProperty(),
            'splitTotal'     => $this->getSplitTotalProperty(),
            'libreMesas'     => $this->view === 'orden'
                ? Mesa::with('sector')->where('is_active', true)->where('status', 'libre')
                    ->where('id', '!=', $this->selectedMesaId ?? 0)->orderBy('name')->get()
                : collect(),
        ]);
    }
}
