<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\Core\Permission;
use App\Models\Core\Role;
use App\Models\Core\SubMenu;
use Yajra\DataTables\Facades\DataTables;

class CmsAccountController extends Controller
{

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = User::with(['activeRole'])->select('users.*');
            return DataTables::of($data)
                ->addColumn('role_name', fn($row) => $row->activeRole->name ?? '')
                ->make(true);
        }

        return view('core.users.index');
    }

    public function create()
    {
        $roles     = Role::orderBy('name')->get();
        $companies = Company::whereIn('status_id', [1, 2])->orderBy('name')->get();
        return view('core.users.create', compact('roles', 'companies'));
    }

    public function store(Request $request)
    {
        $noDefault = static::noDefaultPasswordRule();

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email',
            'password'   => ['required', 'min:8', 'confirmed', $noDefault],
            'role_id'    => 'required|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $user = User::create([
            'name'       => $request->name,
            'email'      => $request->email,
            'password'   => Hash::make($request->password),
            'status_id'  => 1,
            'company_id' => $request->company_id ?: null,
        ]);

        if ($request->role_id) {
            $role = Role::findById($request->role_id);
            $user->assignRole($role);
            $user->active_role_id = $role->id;
            $user->save();
        }

        return redirect()->route('management.accounts.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function edit(string $id)
    {
        $user      = User::with('roles')->findOrFail($id);
        $roles     = Role::orderBy('name')->get();
        $companies = Company::whereIn('status_id', [1, 2])->orderBy('name')->get();
        $modules   = $this->getModulesWithUserPermissions($user);
        return view('core.users.edit', compact('user', 'roles', 'companies', 'modules'));
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $noDefault = static::noDefaultPasswordRule();

        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email|unique:users,email,' . $id,
            'password'   => ['nullable', 'min:8', 'confirmed', $noDefault],
            'role_id'    => 'nullable|exists:roles,id',
            'company_id' => 'nullable|exists:companies,id',
        ]);

        $user->name       = $request->name;
        $user->email      = $request->email;
        $user->status_id  = $request->boolean('active') ? 1 : 2;
        $user->company_id = $request->company_id ?: null;

        if ($request->filled('password')) {
            $user->password            = Hash::make($request->password);
            $user->must_change_password = false;
        }

        $user->syncRoles([]);
        if ($request->role_id) {
            $role = Role::findById($request->role_id);
            $user->assignRole($role);
            $user->active_role_id = $role->id;
        } else {
            $user->active_role_id = null;
        }

        $user->save();

        return redirect()->route('management.accounts.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'No puedes desactivarte a ti mismo.'], 403);
        }

        $user->status_id = $user->status_id == 1 ? 2 : 1;
        $user->save();

        $msg = $user->status_id == 1 ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';
        return response()->json(['status' => 'success', 'message' => $msg]);
    }

    /**
     * Reset user password to default and force change on next login.
     */
    public function resetPassword(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        if ($user->id === auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'Usa la sección de perfil para cambiar tu propia contraseña.'], 403);
        }

        $user->password             = Hash::make(User::DEFAULT_PASSWORD);
        $user->must_change_password = true;
        $user->save();

        return response()->json([
            'status'  => 'success',
            'message' => 'Contraseña reseteada. El usuario deberá cambiarla al iniciar sesión.',
        ]);
    }

    /**
     * Toggle a direct permission for a specific user (not via role).
     */
    public function updateUserPermission(Request $request, string $id)
    {
        $user       = User::findOrFail($id);
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
            $user->givePermissionTo($permission->name);
        } else {
            $user->revokePermissionTo($permission->name);
        }

        return response()->json(['status' => 'success', 'message' => 'Permiso directo actualizado.']);
    }

    /**
     * Remove ALL direct permissions from a user (keeps role permissions).
     */
    public function resetUserPermissions(Request $request, string $id)
    {
        $user = User::findOrFail($id);
        $user->syncPermissions([]);

        return response()->json(['status' => 'success', 'message' => 'Permisos directos reseteados correctamente.']);
    }

    /**
     * Validation closure that rejects the system default password.
     */
    private static function noDefaultPasswordRule(): \Closure
    {
        return function ($attr, $value, $fail) {
            if ($value && mb_strtolower($value) === mb_strtolower(User::DEFAULT_PASSWORD)) {
                $fail('No puedes usar la contraseña por defecto del sistema.');
            }
        };
    }

    /**
     * Build module/permission list showing which direct permissions the user has.
     */
    private function getModulesWithUserPermissions(User $user): \Illuminate\Support\Collection
    {
        $modules = SubMenu::with('menu')
            ->where('status_id', 1)
            ->orderBy('menu_id')
            ->orderBy('order')
            ->get();

        // Direct permissions (not inherited from roles)
        $directPermissionIds = $user->getDirectPermissions()->pluck('id')->toArray();

        $modules->each(function ($module) use ($directPermissionIds) {
            if ($module->permission) {
                $parts = explode('.', $module->permission);
                if (count($parts) > 1) {
                    $resource = end($parts);
                    $module->permissions = Permission::where('name', 'LIKE', '%.' . $resource)->get();

                    $module->permissions->each(function ($perm) use ($directPermissionIds) {
                        $perm->check_alias = in_array($perm->id, $directPermissionIds) ? 1 : 0;
                    });

                    $module->permissions_count = $module->permissions->where('check_alias', 1)->count();
                } else {
                    $module->permissions       = collect([]);
                    $module->permissions_count = 0;
                }
            } else {
                $module->permissions       = collect([]);
                $module->permissions_count = 0;
            }
        });

        return $modules;
    }
}
