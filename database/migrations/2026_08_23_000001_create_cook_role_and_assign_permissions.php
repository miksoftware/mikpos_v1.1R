<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create or update 'cook' role
        $existingRole = DB::table('roles')->where('name', 'cook')->first();
        if (!$existingRole) {
            $roleId = DB::table('roles')->insertGetId([
                'name'         => 'cook',
                'display_name' => 'Cocinero',
                'description'  => 'Acceso exclusivo al panel de comandas/cocina de su área asignada',
                'is_system'    => true,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } else {
            $roleId = $existingRole->id;
            DB::table('roles')->where('id', $roleId)->update([
                'display_name' => 'Cocinero',
                'description'  => 'Acceso exclusivo al panel de comandas/cocina de su área asignada',
                'is_system'    => true,
                'is_active'    => true,
                'updated_at'   => now(),
            ]);
        }

        // 2. Find permission 'kitchen_panel.view'
        $permission = DB::table('permissions')->where('name', 'kitchen_panel.view')->first();

        // If the permission doesn't exist yet, ensure kitchen module exists and create it
        if (!$permission) {
            $module = DB::table('modules')->where('name', 'kitchen')->first();
            $moduleId = $module ? $module->id : DB::table('modules')->insertGetId([
                'name'         => 'kitchen',
                'display_name' => 'Comandas / Cocina',
                'icon'         => 'chef-hat',
                'order'        => 29,
                'is_active'    => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            $permissionId = DB::table('permissions')->insertGetId([
                'module_id'    => $moduleId,
                'name'         => 'kitchen_panel.view',
                'display_name' => 'Ver Panel de Cocina (área asignada)',
                'description'  => 'Permite acceder al panel de preparación de comandas',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
        } else {
            $permissionId = $permission->id;
        }

        // 3. Attach permission to cook role if not already attached
        $existsPivot = DB::table('role_permission')
            ->where('role_id', $roleId)
            ->where('permission_id', $permissionId)
            ->exists();

        if (!$existsPivot) {
            DB::table('role_permission')->insert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('name', 'cook')->first();
        if ($role) {
            DB::table('role_permission')->where('role_id', $role->id)->delete();
            DB::table('user_role')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }
    }
};
