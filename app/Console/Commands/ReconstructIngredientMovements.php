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
            foreach ($sales as $sale) {
                // 1. Check SaleItems (compuesto products)
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

                // 2. Check CuentaItems if sale originated from Mostrador
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

            $this->info("✅ Reconstructed {$movementsCreated} missing ingredient inventory movements with original sale dates.");
            $this->info("✅ Recalculated stock history for " . count($ingredientsAffected) . " ingredients.");

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Error during reconstruction: ' . $e->getMessage());
            return 1;
        }
    }
}
