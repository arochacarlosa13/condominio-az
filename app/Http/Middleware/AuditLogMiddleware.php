<?php

namespace App\Http\Middleware;

use App\Models\AuditTrail;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request and log mutations into audit_trails.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only log modifying methods or login/logout
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            try {
                $user = $request->user();
                $action = match ($request->method()) {
                    'POST' => 'crear',
                    'PUT', 'PATCH' => 'modificar',
                    'DELETE' => 'eliminar',
                    default => strtolower($request->method()),
                };

                if (str_contains($request->path(), 'login')) {
                    $action = 'login';
                } elseif (str_contains($request->path(), 'logout')) {
                    $action = 'logout';
                }

                $pathParts = explode('/', trim($request->path(), '/'));
                $modulo = count($pathParts) > 2 ? $pathParts[2] : ($pathParts[1] ?? 'general');

                AuditTrail::create([
                    'user_id' => $user?->id,
                    'condominio_id' => $user?->condominio_id,
                    'accion' => $action,
                    'modelo' => ucfirst($modulo),
                    'modelo_id' => is_numeric(end($pathParts)) ? (int) end($pathParts) : null,
                    'datos_nuevos' => $request->except(['password', 'password_confirmation', 'token']),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'url' => $request->fullUrl(),
                    'descripcion' => "Acción {$action} en módulo {$modulo}",
                ]);
            } catch (\Throwable $e) {
                // Silently continue so audit logging never crashes the app
                logger()->error('Error registering audit trail: ' . $e->getMessage());
            }
        }

        return $response;
    }
}
