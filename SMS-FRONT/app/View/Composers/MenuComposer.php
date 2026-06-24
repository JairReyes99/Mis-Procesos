<?php

namespace App\View\Composers;

use Illuminate\View\View;
use App\Models\Core\Menu;
use Illuminate\Support\Facades\Auth;

class MenuComposer
{
    public function compose(View $view): void
    {
        if (!Auth::check()) {
            return;
        }

        $menus = Menu::with([
            'subMenus' => function ($query) {
                $query->where('visible_menu', true)
                    ->where('status_id', 1)
                    ->orderBy('order');
            }
        ])
            ->where('visible_menu', true)
            ->where('status_id', 1)
            ->orderBy('order')
            ->get();

        // Filter submenus by user permission
        foreach ($menus as $menu) {
            $filtered = $menu->subMenus->filter(function ($subMenu) {
                if (empty($subMenu->permission)) {
                    return true;
                }
                return Auth::user()->can($subMenu->permission);
            });
            $menu->setRelation('subMenus', $filtered);
        }

        // Remove menus with no visible submenus
        $menus = $menus->filter(function ($menu) {
            return $menu->subMenus->isNotEmpty();
        });

        $view->with('asideMenus', $menus);
    }
}
