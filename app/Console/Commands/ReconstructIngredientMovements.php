<?php

namespace App\Console\Commands;

use App\Models\Ingredient;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Refund;
use App\Models\Sale;
use App\Models\SystemDocument;
use App\Models\Cuenta;
use App\Models\CuentaItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconstructIngredientMovements extends Command
{
    protected $signature = 'inventory:reconstruct-ingredient-movements {--force : Run without confirmation}';
    protected $description = 'Reconstruct missing historical inventory movements for ingredients from sales and refunds';

    public function handle(): int
    {
        $this->info('Starting reconstruction of ingredient inventory movements...');

        $saleDocument = SystemDocument::where('code', 'sale')->first();
        $refundDocument = SystemDocument::where('code', 'refund')->first();
        $purchaseDocument = SystemDocument::where('code', 'purchase')->first();
        $adjustmentDocument = SystemDocument::where('code', 'adjustment')->first();

        if (!$saleDocument) {
            $this->error('System document "sale" not found.');
            return 1;
        }

        $sales = Sale::where('status', 'completed')
            ->with(['items.product.ingredients', 'branch'])
            ->orderBy('created_at', 'asc')
            ->get();

        $this->info("Found {$sales->count()} completed sales to analyze.");

        $movementsCreated = 0;
        $ingredientsAffected = [];

        DB::beginTransaction();

        try {
            // 0. Process ActivityLogs for Ingredient stock initializations and manual edits
            $ingredientLogs = \App\Models\ActivityLog::where(function ($q) {
                    $q->where('module', 'ingredients')
                      ->orWhere('model_type', Ingredient::class)
                      ->orWhere('model_type', 'ingredients');
                })
                ->whereIn('action', ['create', 'update'])
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($ingredientLogs as $log) {
                $ingId = $log->model_id;
                if (!$ingId) continue;

                $newVal = $log->new_values ?? [];
                $oldVal = $log->old_values ?? [];

                $newStock = isset($newVal['stock']) && $newVal['stock'] !== '' ? (float) $newVal['stock'] : null;
                $oldStock = isset($oldVal['stock']) && $oldVal['stock'] !== '' ? (float) $oldVal['stock'] : null;

                if ($newStock === null) continue;

                $qty = 0;
                $type = 'in';

                if ($log->action === 'create') {
                    if ($newStock > 0) {
                        $qty = $newStock;
                        $type = 'in';
                    }
                } else {
                    if ($oldStock !== null) {
                        $diff = $newStock - $oldStock;
                        if ($diff > 0) {
                            $qty = $diff;
                            $type = 'in';
                        } elseif ($diff < 0) {
                            $qty = abs($diff);
                            $type = 'out';
                        }
                    } elseif ($newStock > 0) {
                        $qty = $newStock;
                        $type = 'in';
                    }
                }

                if ($qty <= 0) continue;

                $movement = InventoryMovement::where('reference_type', \App\Models\ActivityLog::class)
                    ->where('reference_id', $log->id)
                    ->where('ingredient_id', $ingId)
                    ->first();

                if (!$movement) {
                    $movement = new InventoryMovement([
                        'system_document_id' => $adjustmentDocument?->id ?? $saleDocument->id,
                        'document_number' => 'LOG-' . $log->id,
                        'ingredient_id' => $ingId,
                        'branch_id' => $log->branch_id ?? (auth()->check() ? auth()->user()->branch_id : 1),
                        'user_id' => $log->user_id ?? (auth()->check() ? auth()->id() : 1),
                        'movement_type' => $type,
                        'quantity' => $qty,
                        'stock_before' => 0,
                        'stock_after' => 0,
                        'unit_cost' => 0,
                        'total_cost' => 0,
                        'reference_type' => \App\Models\ActivityLog::class,
                        'reference_id' => $log->id,
                        'notes' => "Ajuste / Carga inicial de ingrediente (Log #{$log->id})",
                        'movement_date' => $log->created_at->toDateString(),
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

            // 1. Process Purchases for ingredients
            $purchases = \App\Models\Purchase::where('status', 'completed')
                ->with('items')
                ->get();

            foreach ($purchases as $purchase) {
                foreach ($purchase->items as $item) {
                    if (!$item->ingredient_id) continue;
                    $qty = (float) $item->quantity;
                    if ($qty <= 0) continue;

                    $movement = InventoryMovement::where('reference_type', \App\Models\Purchase::class)
                        ->where('reference_id', $purchase->id)
                        ->where('ingredient_id', $item->ingredient_id)
                        ->first();

                    if (!$movement) {
                        $movement = new InventoryMovement([
                            'system_document_id' => $purchaseDocument?->id ?? $saleDocument->id,
                            'document_number' => $purchase->purchase_number,
                            'ingredient_id' => $item->ingredient_id,
                            'branch_id' => $purchase->branch_id,
                            'user_id' => $purchase->user_id,
                            'movement_type' => 'in',
                            'quantity' => $qty,
                            'stock_before' => 0,
                            'stock_after' => 0,
                            'unit_cost' => (float) $item->unit_cost,
                            'total_cost' => (float) $item->total,
                            'reference_type' => \App\Models\Purchase::class,
                            'reference_id' => $purchase->id,
                            'notes' => "Compra #{$purchase->purchase_number}",
                            'movement_date' => $purchase->purchase_date ? $purchase->purchase_date->toDateString() : $purchase->created_at->toDateString(),
                        ]);
                        $movement->created_at = $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->setTimeFrom($purchase->created_at) : $purchase->created_at;
                        $movement->updated_at = $purchase->created_at;
                        $movement->save();

                        $movementsCreated++;
                    } else {
                        $movement->created_at = $purchase->purchase_date ? \Carbon\Carbon::parse($purchase->purchase_date)->setTimeFrom($purchase->created_at) : $purchase->created_at;
                        $movement->updated_at = $purchase->created_at;
                        $movement->save();
                    }
                    $ingredientsAffected[$item->ingredient_id] = true;
                }
            }

            foreach ($sales as $sale) {
                // Check SaleItems (compuesto products)
                foreach ($sale->items as $item) {
                    if (!$item->product_id) continue;
                    $product = $item->product;
                    if (!$product || $product->product_type !== 'compuesto') continue;

                    foreach ($product->ingredients as $ingredient) {
                        $deductQty = (float) $ingredient->pivot->quantity * (float) $item->quantity;
                        if ($deductQty <= 0) continue;

                        $movement = InventoryMovement::where('reference_type', Sale::class)
                            ->where('reference_id', $sale->id)
                            ->where('ingredient_id', $ingredient->id)
                            ->first();

                        if (!$movement) {
                            $movement = new InventoryMovement([
                                'system_document_id' => $saleDocument->id,
                                'document_number' => $sale->invoice_number,
                                'ingredient_id' => $ingredient->id,
                                'branch_id' => $sale->branch_id,
                                'user_id' => $sale->user_id,
                                'movement_type' => 'out',
                                'quantity' => $deductQty,
                                'stock_before' => 0,
                                'stock_after' => 0,
                                'unit_cost' => (float) ($ingredient->purchase_price ?? 0),
                                'total_cost' => (float) ($ingredient->purchase_price ?? 0) * $deductQty,
                                'reference_type' => Sale::class,
                                'reference_id' => $sale->id,
                                'notes' => "Venta #{$sale->invoice_number} (Ingrediente de: {$product->name})",
                                'movement_date' => $sale->created_at->toDateString(),
                            ]);
                            $movement->created_at = $sale->created_at;
                            $movement->updated_at = $sale->created_at;
                            $movement->save();

                            $movementsCreated++;
                        } else {
                            $movement->created_at = $sale->created_at;
                            $movement->updated_at = $sale->created_at;
                            $movement->save();
                        }
                        $ingredientsAffected[$ingredient->id] = true;
                    }
                }

                // Check CuentaItems if sale originated from Mostrador
                $cuenta = Cuenta::where('sale_id', $sale->id)->first();
                if ($cuenta) {
                    $cuentaItems = CuentaItem::where('cuenta_id', $cuenta->id)
                        ->with(['selectedIngredients.ingredient', 'ingredient'])
                        ->get();

                    foreach ($cuentaItems as $ci) {
                        // Standalone ingredient
                        if ($ci->ingredient_id) {
                            $ingId = $ci->ingredient_id;
                            $ing = $ci->ingredient ?? Ingredient::find($ingId);
                            $qty = (float) $ci->quantity;

                            if ($qty > 0 && $ing) {
                                $movement = InventoryMovement::where('reference_type', Sale::class)
                                    ->where('reference_id', $sale->id)
                                    ->where('ingredient_id', $ingId)
                                    ->first();

                                if (!$movement) {
                                    $movement = new InventoryMovement([
                                        'system_document_id' => $saleDocument->id,
                                        'document_number' => $sale->invoice_number,
                                        'ingredient_id' => $ingId,
                                        'branch_id' => $sale->branch_id,
                                        'user_id' => $sale->user_id,
                                        'movement_type' => 'out',
                                        'quantity' => $qty,
                                        'stock_before' => 0,
                                        'stock_after' => 0,
                                        'unit_cost' => (float) ($ing->purchase_price ?? 0),
                                        'total_cost' => (float) ($ing->purchase_price ?? 0) * $qty,
                                        'reference_type' => Sale::class,
                                        'reference_id' => $sale->id,
                                        'notes' => "Venta #{$sale->invoice_number} (Mostrador)",
                                        'movement_date' => $sale->created_at->toDateString(),
                                    ]);
                                    $movement->created_at = $sale->created_at;
                                    $movement->updated_at = $sale->created_at;
                                    $movement->save();

                                    $movementsCreated++;
                                } else {
                                    $movement->created_at = $sale->created_at;
                                    $movement->updated_at = $sale->created_at;
                                    $movement->save();
                                }
                                $ingredientsAffected[$ingId] = true;
                            }
                        }

                        // Selected group ingredients
                        if ($ci->selectedIngredients) {
                            foreach ($ci->selectedIngredients as $sel) {
                                $ingId = $sel->ingredient_id;
                                $ing = $sel->ingredient ?? Ingredient::find($ingId);
                                $qty = (float) $ci->quantity;

                                if ($qty > 0 && $ing) {
                                    $movement = InventoryMovement::where('reference_type', Sale::class)
                                        ->where('reference_id', $sale->id)
                                        ->where('ingredient_id', $ingId)
                                        ->first();

                                    if (!$movement) {
                                        $movement = new InventoryMovement([
                                            'system_document_id' => $saleDocument->id,
                                            'document_number' => $sale->invoice_number,
                                            'ingredient_id' => $ingId,
                                            'branch_id' => $sale->branch_id,
                                            'user_id' => $sale->user_id,
                                            'movement_type' => 'out',
                                            'quantity' => $qty,
                                            'stock_before' => 0,
                                            'stock_after' => 0,
                                            'unit_cost' => (float) ($ing->purchase_price ?? 0),
                                            'total_cost' => (float) ($ing->purchase_price ?? 0) * $qty,
                                            'reference_type' => Sale::class,
                                            'reference_id' => $sale->id,
                                            'notes' => "Venta #{$sale->invoice_number} (Ingrediente opcional)",
                                            'movement_date' => $sale->created_at->toDateString(),
                                        ]);
                                        $movement->created_at = $sale->created_at;
                                        $movement->updated_at = $sale->created_at;
                                        $movement->save();

                                        $movementsCreated++;
                                    } else {
                                        $movement->created_at = $sale->created_at;
                                        $movement->updated_at = $sale->created_at;
                                        $movement->save();
                                    }
                                    $ingredientsAffected[$ingId] = true;
                                }
                            }
                        }
                    }
                }
            }

            // 3. Process Refunds
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
                                'document_number' => $refund->number,
                                'ingredient_id' => $ingredient->id,
                                'branch_id' => $refund->sale?->branch_id ?? auth()->user()->branch_id,
                                'user_id' => $refund->user_id ?? auth()->id(),
                                'movement_type' => 'in',
                                'quantity' => $toReturn,
                                'stock_before' => 0,
                                'stock_after' => 0,
                                'unit_cost' => (float) ($ingredient->purchase_price ?? 0),
                                'total_cost' => (float) ($ingredient->purchase_price ?? 0) * $toReturn,
                                'reference_type' => Refund::class,
                                'reference_id' => $refund->id,
                                'notes' => "Devolución {$refund->number} (Ingrediente de: {$product->name})",
                                'movement_date' => $refund->created_at->toDateString(),
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

            // Also include any ingredients that already had movements
            $existingIngIds = InventoryMovement::whereNotNull('ingredient_id')->pluck('ingredient_id')->unique()->toArray();
            foreach ($existingIngIds as $id) {
                $ingredientsAffected[$id] = true;
            }

            // 4. Recalculate stock_before and stock_after backwards from current stock for all affected ingredients
            foreach (array_keys($ingredientsAffected) as $ingredientId) {
                $ingredient = Ingredient::find($ingredientId);
                if (!$ingredient) continue;

                $movements = InventoryMovement::where('ingredient_id', $ingredientId)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                if ($movements->isEmpty()) continue;

                $runningStock = (float) $ingredient->stock;

                for ($i = $movements->count() - 1; $i >= 0; $i--) {
                    $m = $movements[$i];
                    $stockAfter = $runningStock;
                    $qty = (float) $m->quantity;

                    if ($m->movement_type === 'in') {
                        $stockBefore = $stockAfter - $qty;
                    } else {
                        $stockBefore = $stockAfter + $qty;
                    }

                    $m->stock_before = $stockBefore;
                    $m->stock_after = $stockAfter;
                    $m->save();

                    $runningStock = $stockBefore;
                }
            }

            DB::commit();

            $this->info("✅ Reconstructed {$movementsCreated} missing ingredient inventory movements with original dates.");
            $this->info("✅ Recalculated stock history for " . count($ingredientsAffected) . " ingredients.");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during reconstruction: ' . $e->getMessage());
            return 1;
        }
    }
}
