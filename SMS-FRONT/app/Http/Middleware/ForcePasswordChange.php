<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    protected array $except = [
        'profile*',
        'logout',
        'login',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && Auth::user()->must_change_password) {
            foreach ($this->except as $pattern) {
                if ($request->is($pattern)) {
                    return $next($request);
                }
            }

            if ($request->ajax()) {
                return response()->json([
                    'status'   => 'error',
                    'message'  => 'Debes cambiar tu contraseña antes de continuar.',
                    'redirect' => route('profile.index'),
                ], 403);
            }

            return redirect()->route('profile.index')
                ->with('warning', 'Debes cambiar tu contraseña antes de continuar. Esta es una contraseña temporal.');
        }

        return $next($request);
    }
}
