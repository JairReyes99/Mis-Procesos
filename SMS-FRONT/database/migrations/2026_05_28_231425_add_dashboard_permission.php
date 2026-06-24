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
            'name'       => 'ver.dashboard',
            'guard_name' => 'web',
        ]);

        Role::where('name', 'Administrador')->first()?->givePermissionTo($permission);
        Role::where('name', 'Empresa')->first()?->givePermissionTo($permission);

        // Corregir permiso del submenú Dashboard (fue creado con ver.campanas)
        DB::table('sub_menus')
            ->where('route', 'dashboard')
            ->update(['permission' => 'ver.dashboard']);
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permission = Permission::where('name', 'ver.dashboard')->first();

        if ($permission) {
            Role::where('name', 'Administrador')->first()?->revokePermissionTo($permission);
            Role::where('name', 'Empresa')->first()?->revokePermissionTo($permission);
            $permission->delete();
        }

        DB::table('sub_menus')
            ->where('route', 'dashboard')
            ->update(['permission' => 'ver.campanas']);
    }
};
