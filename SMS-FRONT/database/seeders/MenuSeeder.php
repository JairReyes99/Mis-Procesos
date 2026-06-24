<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Catalog\Status;
use App\Models\Core\Menu;
use App\Models\Core\SubMenu;

class MenuSeeder extends Seeder
{
    public function run()
    {
        $statuses = [
            ['id' => 1, 'name' => 'Activo',    'description' => 'Registro Activo'],
            ['id' => 2, 'name' => 'Inactivo',  'description' => 'Registro Inactivo'],
            ['id' => 3, 'name' => 'Eliminado', 'description' => 'Registro Eliminado'],
        ];

        foreach ($statuses as $status) {
            \Illuminate\Support\Facades\DB::table('statuses')->updateOrInsert(
                ['id' => $status['id']],
                array_merge($status, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // Menus — use firstOrCreate so re-running the seeder does not create duplicates
        $adminMenu = Menu::updateOrCreate(
            ['name' => 'Administración'],
            [
                'icon'     => 'ti ti-settings',
                'order'    => 1,
                'status_id' => 1,
            ]
        );

        // SubMenus — keyed by route to avoid duplicates on re-seed
        $subMenus = [
            [
                'name'       => 'Menú',
                'route'      => 'management.menus.index',
                'permission' => 'ver.menus',
                'order'      => 1,
            ],
            [
                'name'       => 'Submenú',
                'route'      => 'management.submenus.index',
                'permission' => 'ver.submenus',
                'order'      => 2,
            ],
            [
                'name'       => 'Roles y permisos',
                'route'      => 'management.roles.index',
                'permission' => 'ver.roles',
                'order'      => 3,
            ],
            [
                'name'       => 'Cuentas',
                'route'      => 'management.accounts.index',
                'permission' => 'ver.usuarios',
                'order'      => 4,
            ],
            [
                'name'       => 'Empresas',
                'route'      => 'management.companies.index',
                'permission' => 'ver.empresas',
                'order'      => 5,
            ],
            [
                'name'       => 'Configuración',
                'route'      => 'management.settings.index',
                'permission' => 'editar.configuracion',
                'order'      => 6,
            ],
        ];

        foreach ($subMenus as $data) {
            SubMenu::firstOrCreate(
                ['route' => $data['route']],
                array_merge($data, [
                    'menu_id'   => $adminMenu->id,
                    'icon'      => 'menu-bullet menu-bullet-dot',
                    'status_id' => 1,
                ])
            );
        }

        // Menú SMS
        $smsMenu = Menu::updateOrCreate(
            ['name' => 'SMS'],
            [
                'icon'      => 'ti ti-message-dots',
                'order'     => 2,
                'status_id' => 1,
            ]
        );

        $smsSubMenus = [
            [
                'name'       => 'Dashboard',
                'route'      => 'dashboard',
                'permission' => 'ver.dashboard',
                'order'      => 1,
            ],
            [
                'name'       => 'Campañas',
                'route'      => 'sms.campaigns.index',
                'permission' => 'ver.campanas',
                'order'      => 2,
            ],
        ];

        foreach ($smsSubMenus as $data) {
            SubMenu::firstOrCreate(
                ['route' => $data['route']],
                array_merge($data, [
                    'menu_id'   => $smsMenu->id,
                    'icon'      => 'menu-bullet menu-bullet-dot',
                    'status_id' => 1,
                ])
            );
        }
    }
}
