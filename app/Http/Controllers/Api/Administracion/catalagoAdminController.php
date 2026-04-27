<?php

namespace App\Http\Controllers\Api\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;

class catalagoAdminController extends Controller
{
 public function rolesConPermisos()
    {
        $roles = Role::with([
            'permisos.modulo'
        ])
        ->select('id', 'nombre', 'descripcion')
        ->orderBy('nombre')
        ->get();

        $data = $roles->map(function ($rol) {
            $permisosAgrupados = $rol->permisos
                ->groupBy(function ($permiso) {
                    return optional($permiso->modulo)->descripcion ?? 'SIN MÓDULO';
                })
                ->map(function ($permisos, $modulo) {
                    return [
                        'modulo' => $modulo,
                        'numero_permisos' => $permisos->count(),
                        'permisos' => $permisos->map(function ($permiso) {
                            return [
                                'id' => $permiso->id,
                                'nombre' => $permiso->nombre,
                                'descripcion' => $permiso->descripcion,
                                'modulo_id' => $permiso->modulo_id,
                            ];
                        })->values(),
                    ];
                })
                ->values();

            return [
                'id' => $rol->id,
                'nombre' => $rol->nombre,
                'descripcion' => $rol->descripcion,
                'numero_permisos' => $rol->permisos->count(),
                'modulos' => $permisosAgrupados,
            ];
        });

        return response()->json([
            'ok' => true,
            'message' => 'Catálogo de roles con permisos agrupados por módulo obtenido correctamente',
            'data' => $data
        ], 200);
    }
}
