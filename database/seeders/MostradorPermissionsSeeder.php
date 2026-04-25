<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;

class MostradorPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        if (Module::where('name', 'mostrador')->exists()) {
            $this->command->info('mostrador already exists, skipping.');
            return;
        }

        $module = Module::create([
            'name'         => 'mostrador',
            'display_name' => 'Mostrador',
            'icon'         => 'table',
            'order'        => 27,
        ]);

        $permissions = [
            ['name' => 'mostrador.view',   'display_name' => 'Ver Mostrador'],
            ['name' => 'mostrador.access', 'display_name' => 'Acceder al Mostrador'],
        ];

        foreach ($permissions as $permission) {
            $module->permissions()->create($permission);
        }

        $this->command->info('Created module: mostrador');
    }
}
