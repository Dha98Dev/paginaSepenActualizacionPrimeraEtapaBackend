<?php

namespace App\Http\Controllers\Api\Prensa\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModulosCatalogoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $modulos = Modulo::with('submodulos')
                ->orderBy('descripcion', 'asc')
                ->get()
                ->map(function ($modulo) {
                    return [
                        'label' => mb_strtolower($modulo->descripcion),
                        'value' => $modulo->id,
                        'submodulos' => $modulo->submodulos
                            ->sortBy('submodulo_descripcion')
                            ->values()
                            ->map(function ($submodulo) {
                                return [
                                    'label' => $submodulo->submodulo_descripcion,
                                    'value' => $submodulo->id,
                                ];
                            }),
                    ];
                });

            return response()->json([
                'ok' => true,
                'message' => 'Módulos obtenidos correctamente',
                'data' => $modulos
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al obtener los módulos',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
