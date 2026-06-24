<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShareModulePermissions
{
    /**
     * Comparte las variables de permisos estándar del módulo con todas las vistas.
     *
     * Uso en rutas:  middleware('module.perms:menus')
     * Variables disponibles en vistas y JS:
     *   $p_ver, $p_crear, $p_editar, $p_eliminar
     *
     * @param  string|null  $module  Nombre del módulo (ej. 'menus', 'usuarios', 'roles')
     */
    public function handle(Request $request, Closure $next, ?string $module = null): mixed
    {
        $p_ver      = false;
        $p_crear    = false;
        $p_editar   = false;
        $p_eliminar = false;

        if (Auth::check() && $module) {
            $user = Auth::user();

            $p_ver      = $user->can("ver.{$module}");
            $p_crear    = $user->can("crear.{$module}");
            $p_editar   = $user->can("editar.{$module}");
            $p_eliminar = $user->can("eliminar.{$module}");
        }

        view()->share(compact('p_ver', 'p_crear', 'p_editar', 'p_eliminar'));

        return $next($request);
    }
}
