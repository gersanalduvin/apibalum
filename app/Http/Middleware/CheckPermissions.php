<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class CheckPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = Auth::user();

        // Si no hay usuario autenticado, denegar acceso
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado'
            ], 401);
        }

        // Si el usuario es superadmin, permitir acceso total
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Si no se especifican permisos, permitir acceso
        if (empty($permissions)) {
            return $next($request);
        }

        // Verificar si el usuario tiene ALGUNO de los permisos requeridos
        // Soporta OR con | (ej: permission:generar.boletin|generar.consolidado_notas)
        foreach ($permissions as $permissionGroup) {
            $orPermissions = explode('|', $permissionGroup);
            foreach ($orPermissions as $permission) {
                if ($user->hasPermission(trim($permission))) {
                    return $next($request);
                }
            }
        }

        // Si no tiene ninguno
        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para realizar esta acción',
            'required_permission' => implode(' o ', $permissions)
        ], 403);

        return $next($request);
    }
}
