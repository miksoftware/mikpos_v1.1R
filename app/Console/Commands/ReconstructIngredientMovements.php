<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Cuenta;
use App\Models\CuentaItem;
use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SystemDocument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconstructIngredientMovements extends Command
{
    protected $signature = 'inventory:reconstruct-ingredient-movements {--force : Run without confirmation}';
    protected $description = 'Reconstruct ingredient inventory movements from scratch based on authoritative sources (purchases, sales, refunds). Fixes stock contamination.';

    public function handle(): int
    {
        $this->info('=== Reconstrucción de Trazabilidad de Ingredientes ===');
        $this->info('Estrategia: Eliminar y recrear desde fuentes de verdad (cuenta_items, sale_items, purchases).');

        $saleDocument       = SystemDocument::where('code', 'sale')->first();
        $refundDocument     = SystemDocument::where('code', 'refund')->first();
        $purchaseDocument   = SystemDocument::where('code', 'purchase')->first();
        $adjustmentDocument = SystemDocument::where('code', 'adjustment')->first();

        if (!$saleDocument) {
            $this->error('System document "sale" not found.');
            return 1;
        }

        $movementsCreated  = 0;
        $ingredientsAffected = [];

        DB::beginTransaction();

        try {
            // ════════════════════════════════════════════════════════════════════
            // PASO 0: Eliminar TODOS los movimientos de ingredientes vinculados a
            //         ventas de Mostrador (tienen cuenta asociada). Estos serán
            //         recreados correctamente en el Paso 3 desde cuenta_items.
            //
            //         También eliminar movimientos duplicados extra — si para una
            //         sola venta+ingrediente existen >1 filas, borrar los sobrantes.
            //
            //         REGLA FUNDAMENTAL: nunca vincular por rango de tiempo.
            //         La única fuente de verdad para movimientos de Mostrador es
            //         cuenta_items. La única fuente para ventas de POS directo es
            //         sale_items.
            // ════════════════════════════════════════════════════════════════════

            $this->info('[0] Limpiando movimientos de ingredientes de ventas de Mostrador...');

            // Obtener todas las cuentas cerradas (ventas de Mostrador)
            $closedCuentas = Cuenta::where('status', 'cerrada')
                ->whereNotNull('sale_id')
                ->with(['sale.items'])
                ->get();

            $mosradorSaleIds = $closedCuentas->pluck('sale_id')->filter()->unique()->toArray();

            if (!empty($mosradorSaleIds)) {
                // Eliminar TODOS los movimientos de ingredientes de esas ventas.
                // El Paso 3 los recreará con los valores correctos desde cuenta_items.
                $deleted = InventoryMovement::whereIn('reference_id', $mosradorSaleIds)
                    ->where('reference_type', Sale::class)
                    ->whereNotNull('ingredient_id')
                    ->delete();

                $this->info("   Eliminados {$deleted} movimientos de ingredientes de " . count($mosradorSaleIds) . " ventas de Mostrador.");
            }

            // Eliminar también movimientos que aún apuntan a CuentaItem (no se reconvirtieron)
            $allCuentaItemIds = CuentaItem::whereIn('cuenta_id',
                Cuenta::whereNotNull('sale_id')->pluck('id')
            )->pluck('id')->toArray();

            if (!empty($allCuentaItemIds)) {
                $orphaned = InventoryMovement::where('reference_type', CuentaItem::class)
                    ->whereIn('reference_id', $allCuentaItemIds)
                    ->delete();
                if ($orphaned > 0) {
                    $this->info("   Eliminados {$orphaned} movimientos huérfanos que aún apuntaban a CuentaItem.");
                }
            }

            // ════════════════════════════════════════════════════════════════════
            // PASO 1: ActivityLogs → movimientos de ajuste/stock inicial
            // ════════════════════════════════════════════════════════════════════

            $this->info('[1] Procesando ActivityLogs para ajustes y stock inicial...');

            $ingredientLogs = ActivityLog::where(function ($q) {
                    $q->where('module', 'like', '%ingredient%')
                      ->orWhere('model_type', 'like', '%Ingredient%');
                })
                ->whereIn('action', ['create', 'update', 'created', 'updated', 'stock_change', 'adjust'])
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($ingredientLogs as $log) {
                $ingId = $log->model_id;
                if (!$ingId) continue;
                if (!Ingredient::find($ingId)) continue;

                $newVal   = is_array($log->new_values) ? $log->new_values : json_decode($log->new_values ?? '[]', true);
                $oldVal   = is_array($log->old_values) ? $log->old_values : json_decode($log->old_values ?? '[]', true);
                $newStock = isset($newVal['stock']) && $newVal['stock'] !== '' ? (float) $newVal['stock'] : null;
                $oldStock = isset($oldVal['stock']) && $oldVal['stock'] !== '' ? (float) $oldVal['stock'] : null;

                if ($newStock === null) continue;

                $qty  = 0;
                $type = 'in';

                if ($log->action === 'create' || $log->action === 'created') {
                    if ($newStock > 0) { $qty = $newStock; $type = 'in'; }
                } else {
                    if ($oldStock !== null) {
                        $diff = $newStock - $oldStock;
                        if ($diff > 0)      { $qty = $diff;       $type = 'in'; }
                        elseif ($diff < 0)  { $qty = abs($diff);  $type = 'out'; }
                    } elseif ($newStock > 0) {
                        $qty = $newStock; $type = 'in';
                    }
                }

                if ($qty <= 0) continue;

                $movement = InventoryMovement::where('reference_type', ActivityLog::class)
                    ->where('reference_id', $log->id)
                    ->where('ingredient_id', $ingId)
                    ->first();

                if (!$movement) {
                    $movement = new InventoryMovement([
                        'system_document_id' => $adjustmentDocument?->id ?? $saleDocument->id,
                        'document_number'    => 'LOG-' . $log->id,
                        'ingredient_id'      => $ingId,
                        'branch_id'          => $log->branch_id ?? (auth()->check() ? auth()->user()->branch_id : 1),
                        'user_id'            => $log->user_id   ?? (auth()->check() ? auth()->id() : 1),
                        'movement_type'      => $type,
                        'quantity'           => $qty,
                        'stock_before'       => 0,
                        'stock_after'        => 0,
                        'unit_cost'          => 0,
                        'total_cost'         => 0,
                        'reference_type'     => ActivityLog::class,
                        'reference_id'       => $log->id,
                        'notes'              => "Ajuste / Carga inicial de ingrediente (Log #{$log->id})",
                        'movement_date'      => $log->created_at->toDateString(),
                    ]);
                    $movement->created_at = $log->created_at;
                    $movement->updated_at = $log->created_at;
                    $movement->save();
                    $movementsCreated++;
                } else {
                    $movement->created_at = $log->created_at;
                    $movement->updated_at = $log->created_at;
                    $movement->save();
                }
                $ingredientsAffected[$ingId] = true;
            }

            // ════════════════════════════════════════════════════════════════════
            // PASO 2: Compras → movimientos de entrada
            // ════════════════════════════════════════════════════════════════════

            $this->info('[2] Procesando Compras...');

            $purchases = Purchase::where('status', 'completed')->with('items')->get();

            foreach ($purchases as $purchase) {
                foreach ($purchase->items as $item) {
                    if (!$item->ingredient_id) continue;
                    $qty = (float) $item->quantity;
                    if ($qty <= 0) continue;

                    $movement = InventoryMovement::where('reference_type', Purchase::class)
                        ->where('reference_id', $purchase->id)
                        ->where('ingredient_id', $item->ingredient_id)
                        ->first();

                    if (!$movement) {
                        $movement = new InventoryMovement([
                            'system_document_id' => $purchaseDocument?->id ?? $saleDocument->id,
                            'document_number'    => $purchase->purchase_number,
                            'ingredient_id'      => $item->ingredient_id,
                            'branch_id'          => $purchase->branch_id,
                            'user_id'            => $purchase->user_id,
                            'movement_type'      => 'in',
                            'quantity'           => $qty,
                            'stock_before'       => 0,
                            'stock_after'        => 0,
                            'unit_cost'          => (float) $item->unit_cost,
                            'total_cost'         => (float) $item->total,
                            'reference_type'     => Purchase::class,
                            'reference_id'       => $purchase->id,
                            'notes'              => "Compra #{$purchase->purchase_number}",
                            'movement_date'      => $purchase->purchase_date
                                ? $purchase->purchase_date->toDateString()
                                : $purchase->created_at->toDateString(),
                        ]);
                        $movement->created_at = $purchase->purchase_date
                            ? \Carbon\Carbon::parse($purchase->purchase_date)->setTimeFrom($purchase->created_at)
                            : $purchase->created_at;
                        $movement->updated_at = $purchase->created_at;
                        $movement->save();
                        $movementsCreated++;
                    } else {
                        $movement->created_at = $purchase->purchase_date
                            ? \Carbon\Carbon::parse($purchase->purchase_date)->setTimeFrom($purchase->created_at)
                            : $purchase->created_at;
                        $movement->updated_at = $purchase->created_at;
                        $movement->save();
                    }
                    $ingredientsAffected[$item->ingredient_id] = true;
                }
            }

            // ════════════════════════════════════════════════════════════════════
            // PASO 3: Ventas → movimientos de salida de ingredientes
            //
            // Para ventas de Mostrador (tienen Cuenta): la fuente de verdad es
            //   cuenta_items, que refleja exactamente lo que se pidió por mesa.
            //   Un movimiento por cada cuenta_item de ingrediente.
            //
            // Para ventas de POS directo (sin Cuenta): la fuente de verdad es
            //   sale_items, para productos compuestos descontamos sus ingredientes
            //   según la receta (product_ingredients pivot).
            // ════════════════════════════════════════════════════════════════════

            $this->info('[3] Procesando Ventas y creando movimientos de ingredientes...');

            // Construir mapa de ventas que vienen de Mostrador
            $mosradorSaleIdSet = array_flip($mosradorSaleIds);

            $sales = Sale::where('status', 'completed')
                ->with(['items.product.ingredients', 'branch'])
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($sales as $sale) {
                $isMostrador = isset($mosradorSaleIdSet[$sale->id]);

                if ($isMostrador) {
                    // ── Venta de Mostrador: usar cuenta_items como fuente de verdad ──

                    $cuenta = Cuenta::where('sale_id', $sale->id)->first();
                    if (!$cuenta) continue;

                    $cuentaItems = CuentaItem::where('cuenta_id', $cuenta->id)
                        ->with(['selectedIngredients.ingredient', 'ingredient', 'product' => function ($q) {
                            $q->with('ingredients');
                        }])
                        ->get();

                    // Consolidar: ingredient_id → cantidad total para esta venta
                    // (puede haber múltiples cuenta_items del mismo ingrediente)
                    $ingredientQtys = [];

                    foreach ($cuentaItems as $ci) {
                        // Ingrediente vendido directo
                        if ($ci->ingredient_id) {
                            $qty = (float) $ci->quantity;
                            if ($qty > 0) {
                                $ingredientQtys[$ci->ingredient_id] = ($ingredientQtys[$ci->ingredient_id] ?? 0) + $qty;
                            }
                        }

                        // Producto compuesto: descontar ingredientes de receta
                        if ($ci->product_id) {
                            $prod = $ci->product ?? Product::with('ingredients')->find($ci->product_id);
                            if ($prod && $prod->product_type === 'compuesto') {
                                foreach ($prod->ingredients as $ing) {
                                    $deduct = (float) $ing->pivot->quantity * (float) $ci->quantity;
                                    if ($deduct > 0) {
                                        $ingredientQtys[$ing->id] = ($ingredientQtys[$ing->id] ?? 0) + $deduct;
                                    }
                                }
                            }
                        }

                        // Ingredientes seleccionados de grupos
                        if ($ci->selectedIngredients) {
                            foreach ($ci->selectedIngredients as $sel) {
                                $qty = (float) $ci->quantity;
                                if ($qty > 0 && $sel->ingredient_id) {
                                    $ingredientQtys[$sel->ingredient_id] = ($ingredientQtys[$sel->ingredient_id] ?? 0) + $qty;
                                }
                            }
                        }
                    }

                    // Crear UN movimiento por ingrediente con la cantidad consolidada
                    foreach ($ingredientQtys as $ingId => $totalQty) {
                        $ing = Ingredient::find($ingId);
                        if (!$ing || $totalQty <= 0) continue;

                        $movement = new InventoryMovement([
                            'system_document_id' => $saleDocument->id,
                            'document_number'    => $sale->invoice_number,
                            'ingredient_id'      => $ingId,
                            'branch_id'          => $sale->branch_id,
                            'user_id'            => $sale->user_id,
                            'movement_type'      => 'out',
                            'quantity'           => $totalQty,
                            'stock_before'       => 0,
                            'stock_after'        => 0,
                            'unit_cost'          => (float) ($ing->purchase_price ?? 0),
                            'total_cost'         => (float) ($ing->purchase_price ?? 0) * $totalQty,
                            'reference_type'     => Sale::class,
                            'reference_id'       => $sale->id,
                            'notes'              => "Venta #{$sale->invoice_number} (Mostrador)",
                            'movement_date'      => $sale->created_at->toDateString(),
                        ]);
                        $movement->created_at = $sale->created_at;
                        $movement->updated_at = $sale->created_at;
                        $movement->save();
                        $movementsCreated++;
                        $ingredientsAffected[$ingId] = true;
                    }
                } else {
                    // ── Venta de POS directo: usar sale_items + receta de compuesto ──

                    foreach ($sale->items as $item) {
                        if (!$item->product_id) continue;
                        $product = $item->product;
                        if (!$product || $product->product_type !== 'compuesto') continue;

                        foreach ($product->ingredients as $ingredient) {
                            $deductQty = (float) $ingredient->pivot->quantity * (float) $item->quantity;
                            if ($deductQty <= 0) continue;

                            // Para ventas directas no-Mostrador, revisar si ya existe el movimiento
                            $movement = InventoryMovement::where('reference_type', Sale::class)
                                ->where('reference_id', $sale->id)
                                ->where('ingredient_id', $ingredient->id)
                                ->first();

                            if (!$movement) {
                                $movement = new InventoryMovement([
                                    'system_document_id' => $saleDocument->id,
                                    'document_number'    => $sale->invoice_number,
                                    'ingredient_id'      => $ingredient->id,
                                    'branch_id'          => $sale->branch_id,
                                    'user_id'            => $sale->user_id,
                                    'movement_type'      => 'out',
                                    'quantity'           => $deductQty,
                                    'stock_before'       => 0,
                                    'stock_after'        => 0,
                                    'unit_cost'          => (float) ($ingredient->purchase_price ?? 0),
                                    'total_cost'         => (float) ($ingredient->purchase_price ?? 0) * $deductQty,
                                    'reference_type'     => Sale::class,
                                    'reference_id'       => $sale->id,
                                    'notes'              => "Venta #{$sale->invoice_number} (Ingrediente de: {$product->name})",
                                    'movement_date'      => $sale->created_at->toDateString(),
                                ]);
                                $movement->created_at = $sale->created_at;
                                $movement->updated_at = $sale->created_at;
                                $movement->save();
                                $movementsCreated++;
                            } else {
                                // Actualizar cantidad y fecha si ya existía
                                $movement->quantity   = $deductQty;
                                $movement->created_at = $sale->created_at;
                                $movement->updated_at = $sale->created_at;
                                $movement->save();
                            }
                            $ingredientsAffected[$ingredient->id] = true;
                        }
                    }
                }
            }

            // ════════════════════════════════════════════════════════════════════
            // PASO 4: Devoluciones → movimientos de entrada (retorno de stock)
            // ════════════════════════════════════════════════════════════════════

            $this->info('[4] Procesando Devoluciones...');

            $refunds = Refund::with(['items.product.ingredients', 'sale'])->get();
            foreach ($refunds as $refund) {
                foreach ($refund->items as $item) {
                    if (!$item->product_id) continue;
                    $product = $item->product;
                    if (!$product || $product->product_type !== 'compuesto') continue;

                    foreach ($product->ingredients as $ingredient) {
                        $toReturn = (float) $ingredient->pivot->quantity * (float) $item->quantity;
                        if ($toReturn <= 0) continue;

                        $movement = InventoryMovement::where('reference_type', Refund::class)
                            ->where('reference_id', $refund->id)
                            ->where('ingredient_id', $ingredient->id)
                            ->first();

                        if (!$movement) {
                            $movement = new InventoryMovement([
                                'system_document_id' => $refundDocument?->id ?? $saleDocument->id,
                                'document_number'    => $refund->number,
                                'ingredient_id'      => $ingredient->id,
                                'branch_id'          => $refund->sale?->branch_id ?? (auth()->check() ? auth()->user()->branch_id : 1),
                                'user_id'            => $refund->user_id ?? (auth()->check() ? auth()->id() : 1),
                                'movement_type'      => 'in',
                                'quantity'           => $toReturn,
                                'stock_before'       => 0,
                                'stock_after'        => 0,
                                'unit_cost'          => (float) ($ingredient->purchase_price ?? 0),
                                'total_cost'         => (float) ($ingredient->purchase_price ?? 0) * $toReturn,
                                'reference_type'     => Refund::class,
                                'reference_id'       => $refund->id,
                                'notes'              => "Devolución {$refund->number} (Ingrediente de: {$product->name})",
                                'movement_date'      => $refund->created_at->toDateString(),
                            ]);
                            $movement->created_at = $refund->created_at;
                            $movement->updated_at = $refund->created_at;
                            $movement->save();
                            $movementsCreated++;
                        } else {
                            $movement->created_at = $refund->created_at;
                            $movement->updated_at = $refund->created_at;
                            $movement->save();
                        }
                        $ingredientsAffected[$ingredient->id] = true;
                    }
                }
            }

            // ════════════════════════════════════════════════════════════════════
            // PASO 5: Stock Inicial para ingredientes sin log de creación
            // ════════════════════════════════════════════════════════════════════

            $this->info('[5] Calculando stock inicial por ingrediente...');

            // Incluir todos los ingredientes que tienen algún movimiento
            $existingIngIds = InventoryMovement::whereNotNull('ingredient_id')
                ->pluck('ingredient_id')->unique()->toArray();
            foreach ($existingIngIds as $id) {
                $ingredientsAffected[$id] = true;
            }

            foreach (array_keys($ingredientsAffected) as $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
                if (!$ingredient) continue;

                // Buscar stock inicial desde ActivityLog de creación
                $firstCreateLog = ActivityLog::where('model_id', $ingredientId)
                    ->where(function ($q) {
                        $q->where('module', 'like', '%ingredient%')
                          ->orWhere('model_type', 'like', '%Ingredient%');
                    })
                    ->whereIn('action', ['create', 'created'])
                    ->orderBy('created_at', 'asc')
                    ->first();

                $initialStockFromLog = null;
                $initialLogDate      = null;

                if ($firstCreateLog) {
                    $newVal   = is_array($firstCreateLog->new_values)
                        ? $firstCreateLog->new_values
                        : json_decode($firstCreateLog->new_values ?? '{}', true);
                    $stockVal = isset($newVal['stock']) && $newVal['stock'] !== '' ? (float) $newVal['stock'] : null;
                    if ($stockVal !== null && $stockVal > 0) {
                        $initialStockFromLog = $stockVal;
                        $initialLogDate      = $firstCreateLog->created_at;
                    }
                }

                $existingInitMovement = InventoryMovement::where('ingredient_id', $ingredientId)
                    ->where('reference_type', 'INITIAL_STOCK')
                    ->first();

                if ($initialStockFromLog !== null) {
                    $earliest = InventoryMovement::where('ingredient_id', $ingredientId)
                        ->where('reference_type', '!=', 'INITIAL_STOCK')
                        ->orderBy('created_at', 'asc')
                        ->first();

                    $initDate = $initialLogDate
                        ? \Carbon\Carbon::parse($initialLogDate)
                        : ($earliest
                            ? \Carbon\Carbon::parse($earliest->created_at)->subSecond()
                            : ($ingredient->created_at ?? now()));

                    if (!$existingInitMovement) {
                        $existingInitMovement = new InventoryMovement([
                            'system_document_id' => $adjustmentDocument?->id ?? $saleDocument->id,
                            'document_number'    => 'STK-INIT-' . $ingredientId,
                            'ingredient_id'      => $ingredientId,
                            'branch_id'          => auth()->check() ? auth()->user()->branch_id : 1,
                            'user_id'            => auth()->check() ? auth()->id() : 1,
                            'movement_type'      => 'in',
                            'quantity'           => $initialStockFromLog,
                            'stock_before'       => 0,
                            'stock_after'        => $initialStockFromLog,
                            'unit_cost'          => (float) ($ingredient->purchase_price ?? 0),
                            'total_cost'         => (float) ($ingredient->purchase_price ?? 0) * $initialStockFromLog,
                            'reference_type'     => 'INITIAL_STOCK',
                            'reference_id'       => $ingredientId,
                            'notes'              => 'Stock Inicial / Carga de Inventario Base',
                            'movement_date'      => $initDate->toDateString(),
                        ]);
                        $existingInitMovement->created_at = $initDate;
                        $existingInitMovement->updated_at = $initDate;
                        $existingInitMovement->save();
                        $movementsCreated++;
                    } else {
                        $existingInitMovement->quantity   = $initialStockFromLog;
                        $existingInitMovement->created_at = $initDate;
                        $existingInitMovement->updated_at = $initDate;
                        $existingInitMovement->save();
                    }
                } else {
                    // Sin log de creación: calcular stock inicial matemáticamente
                    $totalIn   = (float) InventoryMovement::where('ingredient_id', $ingredientId)
                        ->where('movement_type', 'in')
                        ->where('reference_type', '!=', 'INITIAL_STOCK')
                        ->sum('quantity');
                    $totalOut  = (float) InventoryMovement::where('ingredient_id', $ingredientId)
                        ->where('movement_type', 'out')
                        ->sum('quantity');
                    $currentStock  = (float) ($ingredient->stock ?? 0);
                    $initialNeeded = $currentStock + $totalOut - $totalIn;

                    if ($initialNeeded > 0) {
                        $earliest = InventoryMovement::where('ingredient_id', $ingredientId)
                            ->orderBy('created_at', 'asc')->first();
                        $initDate = $earliest
                            ? \Carbon\Carbon::parse($earliest->created_at)->subSecond()
                            : ($ingredient->created_at ?? now());

                        if (!$existingInitMovement) {
                            $existingInitMovement = new InventoryMovement([
                                'system_document_id' => $adjustmentDocument?->id ?? $saleDocument->id,
                                'document_number'    => 'STK-INIT-' . $ingredientId,
                                'ingredient_id'      => $ingredientId,
                                'branch_id'          => auth()->check() ? auth()->user()->branch_id : 1,
                                'user_id'            => auth()->check() ? auth()->id() : 1,
                                'movement_type'      => 'in',
                                'quantity'           => $initialNeeded,
                                'stock_before'       => 0,
                                'stock_after'        => $initialNeeded,
                                'unit_cost'          => (float) ($ingredient->purchase_price ?? 0),
                                'total_cost'         => (float) ($ingredient->purchase_price ?? 0) * $initialNeeded,
                                'reference_type'     => 'INITIAL_STOCK',
                                'reference_id'       => $ingredientId,
                                'notes'              => 'Stock Inicial / Carga de Inventario Base',
                                'movement_date'      => $initDate->toDateString(),
                            ]);
                            $existingInitMovement->created_at = $initDate;
                            $existingInitMovement->updated_at = $initDate;
                            $existingInitMovement->save();
                            $movementsCreated++;
                        } else {
                            $existingInitMovement->quantity   = $initialNeeded;
                            $existingInitMovement->created_at = $initDate;
                            $existingInitMovement->updated_at = $initDate;
                            $existingInitMovement->save();
                        }
                    }
                }
            }

            // ════════════════════════════════════════════════════════════════════
            // PASO 6: Recalcular stock_before / stock_after cronológicamente y
            //         actualizar el stock actual del ingrediente en la BD.
            // ════════════════════════════════════════════════════════════════════

            $this->info('[6] Recalculando stock_before / stock_after para cada ingrediente...');

            foreach (array_keys($ingredientsAffected) as $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
                if (!$ingredient) continue;

                $movements = InventoryMovement::where('ingredient_id', $ingredientId)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $runningStock = 0.0;

                foreach ($movements as $m) {
                    $stockBefore = $runningStock;
                    $qty         = (float) $m->quantity;

                    $stockAfter = $m->movement_type === 'in'
                        ? $stockBefore + $qty
                        : $stockBefore - $qty;

                    $m->stock_before = $stockBefore;
                    $m->stock_after  = $stockAfter;
                    $m->save();

                    $runningStock = $stockAfter;
                }

                // Actualizar el stock real del ingrediente al valor calculado
                $ingredient->stock = $runningStock;
                $ingredient->save();
            }

            DB::commit();

            $this->info('');
            $this->info("✅ Reconstrucción completada.");
            $this->info("   Movimientos creados : {$movementsCreated}");
            $this->info("   Ingredientes ajustados: " . count($ingredientsAffected));

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error durante la reconstrucción: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
