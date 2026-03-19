<?php

namespace App\Http\Controllers\Api\Escalafon\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\GruposEscalafon;
use App\Models\Modulo;
use App\Models\TiposDocumento;
use Illuminate\Http\Request;

class TipoDocumentosController extends Controller
{
    public function obtenerPorModulo(Request $request, string $modulo)
    {
        $moduloTexto = trim($modulo);

        $moduloEncontrado = Modulo::whereRaw('LOWER(descripcion) = ?', [strtolower($moduloTexto)])
            ->first();

        if (!$moduloEncontrado) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el módulo solicitado',
                'modulo_buscado' => $moduloTexto
            ], 404);
        }

        $tiposDocumentos = TiposDocumento::where('modulo_id', $moduloEncontrado->id)
            ->orderBy('tipo_documento', 'asc')
            ->get();

        return response()->json([
            'ok' => true,
            'message' => 'Tipos de documento obtenidos correctamente',
            'modulo' => [
                'id' => $moduloEncontrado->id,
                'descripcion' => $moduloEncontrado->descripcion
            ],
            'data' => $tiposDocumentos
        ], 200);
    }
    public function obtenerGruposEscalafon()
    {
        $grupos = GruposEscalafon::select(
            'id as value',
            'descripcion as label'
        )
            ->orderBy('descripcion', 'asc')
            ->get();

        return response()->json([
            'ok' => true,
            'message' => 'Grupos de escalafón obtenidos correctamente',
            'data' => $grupos
        ], 200);
    }
}
