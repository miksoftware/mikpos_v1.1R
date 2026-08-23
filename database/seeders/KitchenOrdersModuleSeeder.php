<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class KitchenOrdersModuleSeeder extends Seeder
{
    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['name' => 'kitchen'],
            [
                'display_name' => 'Comandas / Cocina',
                'icon'         => 'chef-hat',
                'order'        => 29,
                'is_active'    => true,
            ]
        );

        $permissionsData = [
            ['name' => 'kitchen.view',        'display_name' => 'Ver Comandas de Cocina'],
            ['name' => 'kitchen.manage',      'display_name' => 'Gestionar Comandas (tomar/entregar)'],
            ['name' => 'kitchen.send',        'display_name' => 'Enviar Comandas desde Mostrador'],
            ['name' => 'kitchen_panel.view',  'display_name' => 'Ver Panel de Cocina (área asignada)'],
        ];

        $createdPermissionIds = [];
        foreach ($permissionsData as $p) {
            $perm = Permission::firstOrCreate(
                ['name' => $p['name']],
                [
                    'module_id'    => $module->id,
                    'display_name' => $p['display_name'],
                ]
            );
            $createdPermissionIds[] = $perm->id;
        }

        // Assign all permissions to super_admin
        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($createdPermissionIds);
        }

        // Branch admin & supervisor can view and manage
        foreach (['branch_admin', 'supervisor'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->permissions()->syncWithoutDetaching($createdPermissionIds);
            }
        }

        // Cashier can view and send
        $cashier = Role::where('name', 'cashier')->first();
        if ($cashier) {
            $sendView = Permission::whereIn('name', ['kitchen.view', 'kitchen.send'])->pluck('id')->toArray();
            $cashier->permissions()->syncWithoutDetaching($sendView);
        }

        // Cook role gets kitchen_panel.view
        $cook = Role::firstOrCreate(
            ['name' => 'cook'],
            [
                'display_name' => 'Cocinero',
                'description'  => 'Acceso exclusivo al panel de comandas/cocina de su área asignada',
                'is_system'    => true,
                'is_active'    => true,
            ]
        );

        foreach (['super_admin', 'branch_admin', 'supervisor', 'cook'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            $panelPerm = Permission::where('name', 'kitchen_panel.view')->first();
            if ($role && $panelPerm) {
                $role->permissions()->syncWithoutDetaching([$panelPerm->id]);
            }
        }
    }
}
