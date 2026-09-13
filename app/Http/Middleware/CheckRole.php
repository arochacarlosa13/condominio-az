<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  ...$roles
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        // Master / Super Admin bypasses all checks
        if ($user->esMaster()) {
            return $next($request);
        }

        if (empty($roles)) {
            return $next($request);
        }

        // Check if user's role name or slug matches any of the requested roles
        $userRoleSlug = $user->role ? $user->role->slug : strtolower(str_replace(' ', '-', $user->rol ?? ''));
        $userRoleName = $user->role ? $user->role->nombre : $user->rol;

        foreach ($roles as $role) {
            $r = strtolower(trim($role));
            if (
                $userRoleSlug === $r ||
                strtolower($userRoleName) === $r ||
                ($r === 'admin' && $user->esAdmin()) ||
                ($r === 'master' && $user->esMaster()) ||
                ($r === 'propietario' && $user->esPropietario()) ||
                ($r === 'supervisor' && $user->esSupervisor()) ||
                ($r === 'analista' && $user->esAnalista()) ||
                $user->hasPermission($role)
            ) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'Acceso no autorizado para este recurso o rol',
        ], 403);
    }
}
