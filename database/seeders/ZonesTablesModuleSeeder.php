<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;

class ZonesTablesModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'zones_tables'],
            [
                'display_name' => 'Zonas y Mesas',
                'icon' => 'table-cells',
                'order' => 40,
            ]
        );

        $permissions = [
            ['name' => 'zones_tables.view', 'display_name' => 'Ver zonas y mesas', 'module_id' => $module->id],
            ['name' => 'zones_tables.create', 'display_name' => 'Crear zonas y mesas', 'module_id' => $module->id],
            ['name' => 'zones_tables.edit', 'display_name' => 'Editar zonas y mesas', 'module_id' => $module->id],
            ['name' => 'zones_tables.delete', 'display_name' => 'Eliminar zonas y mesas', 'module_id' => $module->id],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name']],
                $permissionData
            );
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        $branchAdmin = Role::where('name', 'branch_admin')->first();

        $permissionIds = Permission::where('name', 'like', 'zones_tables.%')->pluck('id');

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }

        if ($branchAdmin) {
            $branchAdmin->permissions()->syncWithoutDetaching($permissionIds);
        }
    }
}
