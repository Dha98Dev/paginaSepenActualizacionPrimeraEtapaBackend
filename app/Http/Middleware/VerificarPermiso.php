<?php

namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
  public function handle(Request $request, Closure $next, string ...$permisosRequeridos): Response
    {
        $authUser = $request->user();

        if (!$authUser) {
            return response()->json([
                'ok' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        $user = Usuario::with([
            'roles.permisos',
            'usuario_permisos.permiso'
        ])->find($authUser->id);

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Usuario no encontrado',
            ], 404);
        }

        $permisosRol = $user->roles
            ->flatMap(function ($rol) {
                return $rol->permisos;
            })
            ->pluck('slug')
            ->unique();

        $permitidos = $user->usuario_permisos
            ->where('permitido', true)
            ->pluck('permiso.slug');

        $negados = $user->usuario_permisos
            ->where('permitido', false)
            ->pluck('permiso.slug');

        $permisosFinales = $permisosRol
            ->merge($permitidos)
            ->diff($negados)
            ->unique()
            ->values();

        $tieneAlMenosUno = collect($permisosRequeridos)
            ->contains(fn ($permiso) => $permisosFinales->contains($permiso));

        if (!$tieneAlMenosUno) {
            return response()->json([
                'ok' => false,
                'message' => 'No tienes permiso para realizar esta acción',
                'permisos_requeridos' => $permisosRequeridos,
            ], 403);
        }

        return $next($request);
    }
}
