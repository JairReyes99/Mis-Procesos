<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CmsMenuController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = \App\Models\Core\Menu::select('menus.*');
            return \Yajra\DataTables\Facades\DataTables::of($data)->make(true);
        }

        return view('core.menus.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'visible_menu' => 'required|boolean',
        ]);

        \App\Models\Core\Menu::create([
            'name' => $request->name,
            'icon' => $request->icon ?? 'ni ni-bullet-list-67',
            'order' => $request->order ?? 0,
            'visible_menu' => $request->visible_menu,
            'status_id' => 1 // Active
        ]);

        return response()->json(['success' => 'Menú creado correctamente']);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $menu = \App\Models\Core\Menu::findOrFail($id);
        return response()->json($menu);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer',
            'visible_menu' => 'required|boolean',
        ]);

        $menu = \App\Models\Core\Menu::findOrFail($id);
        $menu->update([
            'name' => $request->name,
            'icon' => $request->icon,
            'order' => $request->order,
            'visible_menu' => $request->visible_menu,
        ]);

        return response()->json(['success' => 'Menú actualizado correctamente']);
    }

    public function destroy(string $id)
    {
        $menu = \App\Models\Core\Menu::findOrFail($id);

        if ($menu->subMenus()->count() > 0) {
            return response()->json(['status' => 'error', 'message' => 'No se puede eliminar un menú que tiene submenús asignados.'], 422);
        }

        $menu->delete();
        return response()->json(['success' => 'Menú eliminado correctamente.']);
    }
}
