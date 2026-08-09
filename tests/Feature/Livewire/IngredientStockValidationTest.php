<?php

namespace Tests\Feature\Livewire;

use App\Livewire\Mostrador;
use App\Livewire\PointOfSale;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\CashReconciliation;
use App\Models\Category;
use App\Models\Cuenta;
use App\Models\Ingredient;
use App\Models\IngredientGroup;
use App\Models\Mesa;
use App\Models\Product;
use App\Models\Role;
use App\Models\Sector;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class IngredientStockValidationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Sector $sector;
    protected Mesa $mesa;
    protected Unit $unit;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::factory()->superAdmin()->create();
        $this->branch = Branch::factory()->create(['is_active' => true]);
        $this->user = User::factory()->create(['branch_id' => $this->branch->id]);
        $this->user->roles()->attach($role->id);

        $this->sector = Sector::create([
            'branch_id' => $this->branch->id,
            'name' => 'Main Sector',
            'is_active' => true,
        ]);

        $this->mesa = Mesa::create([
            'sector_id' => $this->sector->id,
            'name' => 'Mesa 1',
            'status' => 'libre',
            'is_active' => true,
        ]);

        $this->unit = Unit::factory()->create();
    }

    public function test_mostrador_cannot_add_out_of_stock_direct_ingredient(): void
    {
        $this->actingAs($this->user);

        $ingredient = Ingredient::create([
            'name' => 'Bretaña Out',
            'unit_id' => $this->unit->id,
            'stock' => 0.00,
            'purchase_price' => 3000,
            'sale_price' => 5000,
            'manage_inventory' => true,
            'show_in_pos' => true,
            'is_active' => true,
        ]);

        Livewire::test(Mostrador::class)
            ->call('openMesa', $this->mesa->id)
            ->call('addIngredientToCart', $ingredient->id)
            ->assertDispatched('notify', message: "Sin stock disponible para \"Bretaña Out\". Disponible: 0.00", type: 'error');

        $this->assertEquals(0.00, (float) $ingredient->fresh()->stock);
    }

    public function test_mostrador_cannot_increment_out_of_stock_direct_ingredient(): void
    {
        $this->actingAs($this->user);

        $ingredient = Ingredient::create([
            'name' => 'Ginger',
            'unit_id' => $this->unit->id,
            'stock' => 1.00,
            'purchase_price' => 3000,
            'sale_price' => 5000,
            'manage_inventory' => true,
            'show_in_pos' => true,
            'is_active' => true,
        ]);

        // Add 1 (stock goes from 1 to 0)
        $component = Livewire::test(Mostrador::class)
            ->call('openMesa', $this->mesa->id)
            ->call('addIngredientToCart', $ingredient->id);

        $this->assertEquals(0.00, (float) $ingredient->fresh()->stock);

        // Try to increment quantity to 2
        $component->call('incrementQty', 0)
            ->assertDispatched('notify', message: "Stock insuficiente para \"Ginger\". Disponible: 0.00", type: 'error');

        $this->assertEquals(0.00, (float) $ingredient->fresh()->stock);
    }

    public function test_pos_blocks_compuesto_sale_when_aggregated_ingredient_stock_insufficient(): void
    {
        $this->actingAs($this->user);

        $register = CashRegister::create([
            'branch_id' => $this->branch->id,
            'user_id' => $this->user->id,
            'name' => 'Caja 1',
            'is_active' => true,
        ]);

        CashReconciliation::create([
            'branch_id' => $this->branch->id,
            'cash_register_id' => $register->id,
            'opened_by' => $this->user->id,
            'opening_amount' => 100000,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        $ingredient = Ingredient::create([
            'name' => 'Limon',
            'unit_id' => $this->unit->id,
            'stock' => 1.00, // Only 1 available
            'purchase_price' => 500,
            'sale_price' => 1000,
            'manage_inventory' => true,
            'show_in_pos' => true,
            'is_active' => true,
        ]);

        $prod1 = Product::factory()->create([
            'name' => 'Michelada A',
            'product_type' => 'compuesto',
            'manages_inventory' => false,
        ]);
        $prod1->ingredients()->attach($ingredient->id, ['quantity' => 1]);

        $prod2 = Product::factory()->create([
            'name' => 'Michelada B',
            'product_type' => 'compuesto',
            'manages_inventory' => false,
        ]);
        $prod2->ingredients()->attach($ingredient->id, ['quantity' => 1]);

        // Add prod1 (takes 1) -> ok
        $pos = Livewire::test(PointOfSale::class)
            ->call('addToCart', $prod1->id);

        // Try adding prod2 (would need 2 total, only 1 stock available) -> should warn/block
        $pos->call('addToCart', $prod2->id)
            ->assertDispatched('notify', message: "Stock insuficiente para el ingrediente \"Limon\". Requerido: 2, Disponible: 1.00", type: 'warning');
    }
}
