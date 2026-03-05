<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class IngredientsModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'ingredients'],
            [
                'display_name' => 'Ingredientes',
                'icon' => 'beaker',
                'order' => 37,
            ]
        );

        $permissions = [
            ['name' => 'ingredients.view', 'display_name' => 'Ver ingredientes', 'module_id' => $module->id],
            ['name' => 'ingredients.create', 'display_name' => 'Crear ingredientes', 'module_id' => $module->id],
            ['name' => 'ingredients.edit', 'display_name' => 'Editar ingredientes', 'module_id' => $module->id],
            ['name' => 'ingredients.delete', 'display_name' => 'Eliminar ingredientes', 'module_id' => $module->id],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();

        $permissionIds = Permission::where('name', 'like', 'ingredients.%')->pluck('id');

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
