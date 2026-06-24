<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\User;
use App\Models\Core\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class CompanyUserController extends Controller
{
    public function index(Request $request, string $company)
    {
        $company = Company::findOrFail($company);

        if ($request->ajax()) {
            $data = User::with('activeRole')
                ->where('company_id', $company->id)
                ->select('users.*');

            return DataTables::of($data)
                ->addColumn('role_name', fn ($r) => $r->activeRole?->name ?? '—')
                ->make(true);
        }

        return view('admin.companies.users.index', compact('company'));
    }

    public function store(Request $request, string $company)
    {
        $company   = Company::findOrFail($company);
        $noDefault = static::noDefaultPasswordRule();

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => ['required', 'min:8', 'confirmed', $noDefault],
        ], [], [
            'name'     => 'nombre',
            'email'    => 'correo electrónico',
            'password' => 'contraseña',
        ]);

        $role = Role::where('name', 'Empresa')->firstOrFail();

        $user = User::create([
            'name'            => $request->name,
            'email'           => $request->email,
            'password'        => Hash::make($request->password),
            'status_id'       => 1,
            'company_id'      => $company->id,
            'active_role_id'  => $role->id,
        ]);

        $user->assignRole($role);

        return response()->json([
            'status'  => 'success',
            'message' => 'Usuario creado correctamente.',
        ]);
    }

    public function toggleStatus(string $company, string $user)
    {
        $company = Company::findOrFail($company);
        $user    = User::where('company_id', $company->id)->findOrFail($user);

        if ($user->id === auth()->id()) {
            return response()->json(['status' => 'error', 'message' => 'No puedes cambiar tu propio estatus.'], 403);
        }

        $user->status_id = $user->status_id == 1 ? 2 : 1;
        $user->save();

        $msg = $user->status_id == 1 ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.';

        return response()->json(['status' => 'success', 'message' => $msg]);
    }

    private static function noDefaultPasswordRule(): \Closure
    {
        return function ($attr, $value, $fail) {
            if ($value && mb_strtolower($value) === mb_strtolower(User::DEFAULT_PASSWORD)) {
                $fail('No puedes usar la contraseña por defecto del sistema.');
            }
        };
    }
}
