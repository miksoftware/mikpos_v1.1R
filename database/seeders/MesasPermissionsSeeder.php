<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class MesasPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            [
                'name' => 'sectors',
                'display_name' => 'Sectores',
                'icon' => 'view-grid',
                'order' => 25,
                'permissions' => [
                    ['name' => 'sectors.view', 'display_name' => 'Ver Sectores'],
                    ['name' => 'sectors.create', 'display_name' => 'Crear Sectores'],
                    ['name' => 'sectors.edit', 'display_name' => 'Editar Sectores'],
                    ['name' => 'sectors.delete', 'display_name' => 'Eliminar Sectores'],
                ],
            ],
            [
                'name' => 'mesas',
                'display_name' => 'Mesas',
                'icon' => 'table',
                'order' => 26,
                'permissions' => [
                    ['name' => 'mesas.view', 'display_name' => 'Ver Mesas'],
                    ['name' => 'mesas.create', 'display_name' => 'Crear Mesas'],
                    ['name' => 'mesas.edit', 'display_name' => 'Editar Mesas'],
                    ['name' => 'mesas.delete', 'display_name' => 'Eliminar Mesas'],
                ],
            ],
        ];

        foreach ($modules as $moduleData) {
            if (Module::where('name', $moduleData['name'])->exists()) {
                $this->command->info($moduleData['name'] . ' already exists, skipping.');
                continue;
            }

            $permissions = $moduleData['permissions'];
            unset($moduleData['permissions']);

            $module = Module::create($moduleData);

            foreach ($permissions as $permissionData) {
                $module->permissions()->create($permissionData);
            }

            $this->command->info('Created module: ' . $moduleData['name']);
        }
    }
}
