<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Core\Role;
use App\Models\Core\Permission;
use Illuminate\Http\Request;

class CmsRoleController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            return \Yajra\DataTables\Facades\DataTables::of(Role::query())->make(true);
        }

        return view('core.roles.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $role = new \Spatie\Permission\Models\Role();
        $modules = $this->getModulesWithPermissions($role);
        return view('core.roles.edit', compact('role', 'modules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:roles,name',
        ]);

        $role = Role::create(['name' => $request->name, 'guard_name' => 'web']);

        return redirect()->route('management.roles.edit', $role->id)->with('success', 'Rol creado correctamente');
    }

    public function edit(string $id)
    {
        $role = Role::findOrFail($id);
        $modules = $this->getModulesWithPermissions($role);
        return view('core.roles.edit', compact('role', 'modules'));
    }

    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|unique:roles,name,' . $id,
        ]);

        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);

        return redirect()->route('management.roles.edit', $role->id)->with('success', 'Rol actualizado correctamente');
    }

    public function updatePermission(Request $request)
    {
        try {
            if ($request->ajax()) {
                $role       = Role::findOrFail($request->role_id);
                $permission = Permission::findOrFail($request->pid);
                $checked    = filter_var($request->checked, FILTER_VALIDATE_BOOLEAN);

                // Only allow granting permissions that the authenticated user themselves possess
                if ($checked && !auth()->user()->hasPermissionTo($permission->name)) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => 'No puedes otorgar un permiso que tú mismo no posees.',
                    ], 403);
                }

                if ($checked) {
                    $role->givePermissionTo($permission->name);
                } else {
                    $role->revokePermissionTo($permission->name);
                }

                return response()->json(['status' => 'success', 'message' => 'Permiso actualizado']);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    public function destroy(string $id)
    {
        $role = Role::findOrFail($id);

        // Prevent deleting roles that have users assigned
        if ($role->users()->count() > 0) {
            return response()->json(['status' => 'error', 'message' => 'No se puede eliminar un rol que tiene usuarios asignados.'], 422);
        }

        $role->delete();
        return response()->json(['status' => 'success', 'message' => 'Rol eliminado correctamente.']);
    }

    private function getModulesWithPermissions($role)
    {
        $modules = \App\Models\Core\SubMenu::with('menu')->where('status_id', 1)->orderBy('menu_id')->get();

        // Get all role permissions IDs if role exists
        $rolePermissions = $role->exists ? $role->permissions->pluck('id')->toArray() : [];

        $modules->each(function ($module) use ($rolePermissions) {
            if ($module->permission) {
                // Extract resource name (e.g., 'ver.menus' -> 'menus')
                $parts = explode('.', $module->permission);
                if (count($parts) > 1) {
                    $resource = end($parts);
                    // Find all permissions ending with .$resource
                    $module->permissions = Permission::where('name', 'LIKE', '%.' . $resource)->get();

                    // Add check_alias flag
                    $module->permissions->map(function ($perm) use ($rolePermissions) {
                        $perm->check_alias = in_array($perm->id, $rolePermissions) ? 1 : 0;
                        return $perm;
                    });

                    $module->permissions_count = $module->permissions->where('check_alias', 1)->count();
                } else {
                    $module->permissions = collect([]);
                    $module->permissions_count = 0;
                }
            } else {
                $module->permissions = collect([]);
                $module->permissions_count = 0;
            }
        });

        return $modules;
    }
}
