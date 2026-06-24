<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name'       => 'ver.pagos',
            'guard_name' => 'web',
        ]);

        Role::where('name', 'Administrador')->first()?->givePermissionTo($permission);
        Role::where('name', 'Empresa')->first()?->givePermissionTo($permission);

        // Crear menú Finanzas
        $finanzasId = DB::table('menus')->insertGetId([
            'name'         => 'Finanzas',
            'icon'         => 'ti ti-wallet',
            'visible_menu' => true,
            'order'        => 3,
            'status_id'    => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        if (!DB::table('sub_menus')->where('route', 'payments.index')->exists()) {
            DB::table('sub_menus')->insert([
                'menu_id'    => $finanzasId,
                'name'       => 'Pagos',
                'route'      => 'payments.index',
                'icon'       => 'bi bi-credit-card',
                'permission' => 'ver.pagos',
                'order'      => 1,
                'status_id'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::where('name', 'ver.pagos')->first();

        if ($permission) {
            Role::where('name', 'Administrador')->first()?->revokePermissionTo($permission);
            Role::where('name', 'Empresa')->first()?->revokePermissionTo($permission);
            $permission->delete();
        }

        DB::table('sub_menus')->where('route', 'payments.index')->delete();
        DB::table('menus')->where('name', 'Finanzas')->delete();
    }
};
