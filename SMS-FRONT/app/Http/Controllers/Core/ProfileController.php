<?php

namespace App\Http\Controllers\Core;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        return view('core.profile.index', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
        ]);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->save();

        return redirect()->route('profile.index')
            ->with('success', 'Perfil actualizado correctamente.');
    }

    public function changePassword(Request $request)
    {
        $user = Auth::user();

        $noDefault = function ($attr, $value, $fail) {
            if (mb_strtolower($value) === mb_strtolower(\App\Models\User::DEFAULT_PASSWORD)) {
                $fail('No puedes usar la contraseña por defecto del sistema.');
            }
        };

        if ($user->must_change_password) {
            $request->validate([
                'password' => ['required', 'min:8', 'confirmed', $noDefault],
            ]);
        } else {
            $request->validate([
                'current_password' => 'required',
                'password'         => ['required', 'min:8', 'confirmed', $noDefault],
            ]);

            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'La contraseña actual no es correcta.']);
            }
        }

        $user->password             = Hash::make($request->password);
        $user->must_change_password = false;
        $user->save();

        // Regenerate session token after password change to prevent session fixation
        $request->session()->regenerate();

        return redirect()->route('profile.index')
            ->with('success', 'Contraseña actualizada correctamente.');
    }
}
