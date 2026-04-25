<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class PreparationStationsPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (Module::where('name', 'preparation_stations')->exists()) {
            $this->command->info('preparation_stations module already exists, skipping.');
            return;
        }

        $module = Module::create([
            'name'         => 'preparation_stations',
            'display_name' => 'Módulos de Preparación',
            'icon'         => 'chef-hat',
            'order'        => 28,
        ]);

        $permissions = [
            ['name' => 'preparation_stations.view',   'display_name' => 'Ver Módulos de Preparación'],
            ['name' => 'preparation_stations.create', 'display_name' => 'Crear Módulos de Preparación'],
            ['name' => 'preparation_stations.edit',   'display_name' => 'Editar Módulos de Preparación'],
            ['name' => 'preparation_stations.delete', 'display_name' => 'Eliminar Módulos de Preparación'],
        ];

        foreach ($permissions as $permission) {
            $module->permissions()->create($permission);
        }

        $this->command->info('preparation_stations module and permissions created successfully.');
    }
}
