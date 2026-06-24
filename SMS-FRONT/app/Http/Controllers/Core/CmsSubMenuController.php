<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Menu;
use App\Models\Core\SubMenu;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CmsSubMenuController extends Controller
{


    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = SubMenu::with(['menu'])->select('sub_menus.*');
            return DataTables::of($data)
                ->addColumn('menu_name', fn($row) => $row->menu->name ?? 'N/A')
                ->make(true);
        }

        $menus = Menu::where('status_id', 1)->orderBy('name')->get();
        return view('core.submenus.index', compact('menus'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'menu_id'      => 'required|exists:menus,id',
            'name'         => 'required|string|max:255',
            'route'        => 'nullable|string|max:255',
            'icon'         => 'nullable|string|max:255',
            'permission'   => 'nullable|string|max:255',
            'order'        => 'nullable|integer',
            'visible_menu' => 'required|boolean',
        ]);

        SubMenu::create([
            'menu_id'      => $request->menu_id,
            'name'         => $request->name,
            'route'        => $request->route ?? null,
            'icon'         => $request->icon ?? 'menu-bullet menu-bullet-dot',
            'permission'   => $request->permission ?? null,
            'order'        => $request->order ?? 0,
            'visible_menu' => $request->visible_menu,
            'status_id'    => 1,
        ]);

        return response()->json(['success' => 'Submenú creado correctamente.']);
    }

    public function edit(string $id)
    {
        $submenu = SubMenu::findOrFail($id);
        return response()->json($submenu);
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'menu_id'      => 'required|exists:menus,id',
            'name'         => 'required|string|max:255',
            'route'        => 'nullable|string|max:255',
            'icon'         => 'nullable|string|max:255',
            'permission'   => 'nullable|string|max:255',
            'order'        => 'nullable|integer',
            'visible_menu' => 'required|boolean',
            'status_id'    => 'required|integer',
        ]);

        $submenu = SubMenu::findOrFail($id);
        $submenu->update([
            'menu_id'      => $request->menu_id,
            'name'         => $request->name,
            'route'        => $request->route,
            'icon'         => $request->icon,
            'permission'   => $request->permission,
            'order'        => $request->order,
            'visible_menu' => $request->visible_menu,
            'status_id'    => $request->status_id,
        ]);

        return response()->json(['success' => 'Submenú actualizado correctamente.']);
    }

    public function destroy(string $id)
    {
        $submenu = SubMenu::findOrFail($id);
        $submenu->delete();
        return response()->json(['success' => 'Submenú eliminado correctamente.']);
    }
}
