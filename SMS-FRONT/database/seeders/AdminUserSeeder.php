<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Usuarios
            'ver.usuarios',
            'crear.usuarios',
            'editar.usuarios',
            'eliminar.usuarios',
            // Menús
            'ver.menus',
            'crear.menus',
            'editar.menus',
            'eliminar.menus',
            // Submenús
            'ver.submenus',
            'crear.submenus',
            'editar.submenus',
            'eliminar.submenus',
            // Roles
            'ver.roles',
            'crear.roles',
            'editar.roles',
            'eliminar.roles',
            // Empresas
            'ver.empresas',
            'crear.empresas',
            'editar.empresas',
            'eliminar.empresas',
            // Dashboard
            'ver.dashboard',
            // Campañas
            'ver.campanas',
            'crear.campanas',
            'editar.campanas',
            'eliminar.campanas',
            // Configuración del sistema
            'editar.configuracion',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Rol Administrador — todos los permisos
        $roleAdmin = Role::firstOrCreate(['name' => 'Administrador', 'guard_name' => 'web']);
        $roleAdmin->syncPermissions(Permission::all());

        // Rol Empresa — acceso exclusivo al módulo de Campañas SMS
        $roleEmpresa = Role::firstOrCreate(['name' => 'Empresa', 'guard_name' => 'web']);
        $roleEmpresa->syncPermissions(
            Permission::whereIn('name', [
                'ver.dashboard',
                'ver.campanas',
                'crear.campanas',
                'editar.campanas',
                'eliminar.campanas',
            ])->get()
        );

        // Usuario administrador
        // Password is read from ADMIN_SEED_PASSWORD env variable.
        // Set it in .env before running the seeder (never commit the value).
        $seedPassword = env('ADMIN_SEED_PASSWORD');
        abort_if(!$seedPassword, 500, 'ADMIN_SEED_PASSWORD not set in .env — refusing to seed with no password.');

        $admin = User::firstOrCreate(
            ['email' => 'soporte@core.com'],
            [
                'name'      => 'Soporte Core',
                'password'  => Hash::make($seedPassword),
                'status_id' => 1,
            ]
        );
        $admin->syncRoles([$roleAdmin]);
        $admin->active_role_id = $roleAdmin->id;
        $admin->save();
    }
}
