<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class CookRoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create or update the 'cook' role
        $cook = Role::firstOrCreate(
            ['name' => 'cook'],
            [
                'display_name' => 'Cocinero',
                'description'  => 'Acceso exclusivo al panel de comandas/cocina de su área asignada',
                'is_system'    => true,
                'is_active'    => true,
            ]
        );

        // 2. Ensure kitchen_panel.view permission exists
        $kitchenModule = Module::firstOrCreate(
            ['name' => 'kitchen'],
            [
                'display_name' => 'Comandas / Cocina',
                'icon'         => 'chef-hat',
                'order'        => 29,
                'is_active'    => true,
            ]
        );

        $panelPerm = Permission::firstOrCreate(
            ['name' => 'kitchen_panel.view'],
            [
                'module_id'    => $kitchenModule->id,
                'display_name' => 'Ver Panel de Cocina (área asignada)',
            ]
        );

        // 3. Assign permission to cook role
        if ($cook && $panelPerm) {
            $cook->permissions()->syncWithoutDetaching([$panelPerm->id]);
        }
    }
}
