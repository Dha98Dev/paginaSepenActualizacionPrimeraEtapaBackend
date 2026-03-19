<?php

namespace App\Http\Controllers\Api\Escalafon\documentos;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Documento;
use App\Models\TiposDocumento;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Str;

class DocumentoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'tipo_documento_id' => 'required|integer|exists:tipos_documentos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'documento_padre_id' => 'nullable|integer|exists:documentos,id',
            'grupo_id' => 'nullable|integer|exists:grupos_escalafon,id',
            'archivo' => 'required|file|max:20480',
            'fecha_publicacion' => 'required|date',
        ]);

        DB::beginTransaction();

        try {
            $tipoDocumento = TiposDocumento::findOrFail($request->tipo_documento_id);

            $fechaPublicacion = Carbon::parse($request->fecha_publicacion);
            $anio = $fechaPublicacion->year;

            $slugTipoDocumento = Str::slug($tipoDocumento->tipo_documento, '_');

            $file = $request->file('archivo');

            $nombreOriginal = $file->getClientOriginalName();
            $extension = strtolower($file->getClientOriginalExtension());
            $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);

            $nombreGuardado = time() . '_' . Str::slug($nombreBase, '_') . '.' . $extension;

            $directorio = "escalafon/{$slugTipoDocumento}/{$anio}";
            $ruta = $file->storeAs($directorio, $nombreGuardado, 'public');

            $hashArchivo = hash_file('sha256', $file->getRealPath());

            $archivo = Archivo::create([
                'nombre_original' => $nombreOriginal,
                'nombre_guardado' => $nombreGuardado,
                'ruta' => $ruta,
                'tipo_mime' => $file->getClientMimeType(),
                'extension' => $extension,
                'tamano_bytes' => $file->getSize(),
                'hash_archivo' => $hashArchivo,
                'descripcion' => $request->descripcion,
                'es_publico' => true,
                'estado' => 'ACTIVO',
                'creado_por' => null,
            ]);

            $documento = Documento::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'tipo_documento_id' => $request->tipo_documento_id,
                'grupo_id' => $request->filled('grupo_id') ? $request->grupo_id : null,
                'archivo_id' => $archivo->id,
                'documento_padre_id' => $request->filled('documento_padre_id') ? $request->documento_padre_id : null,
                'anio' => $anio,
                'fecha_publicacion' => $fechaPublicacion,
                'publicado' => true,
            ]);

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Documento registrado correctamente',
                'data' => [
                    'documento' => $documento,
                    'archivo' => $archivo,
                ]
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al registrar el documento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function obtenerBoletines(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
            'grupo_id' => 'required|integer|exists:grupos_escalafon,id',
        ]);

        $tipoDocumento = TiposDocumento::whereRaw('LOWER(tipo_documento) = ?', ['boletín'])->first();

        if (!$tipoDocumento) {
            return response()->json([
                'ok' => false,
                'message' => 'No existe el tipo de documento boletín'
            ], 404);
        }

        $boletines = Documento::with([
            'archivo',
            'tipos_documento',
            'grupos_escalafon',
            'resultados.archivo',
            'resultados.tipos_documento',
            'resultados.grupos_escalafon'
        ])
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->whereYear('fecha_publicacion', $request->anio)
            ->where('grupo_id', $request->grupo_id)
            ->whereNull('documento_padre_id')
            ->where('publicado', true)
            ->orderBy('fecha_publicacion', 'desc')
            ->get();

        return response()->json([
            'ok' => true,
            'message' => 'Boletines obtenidos correctamente',
            'data' => $boletines
        ], 200);
    }
    public function obtenerConvocatoriasConResultado(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
        ]);

        $tipoConvocatoria = TiposDocumento::whereRaw('LOWER(tipo_documento) = ?', ['convocatoria a vacante'])
            ->first();

        if (!$tipoConvocatoria) {
            return response()->json([
                'ok' => false,
                'message' => 'No existe el tipo de documento convocatoria a vacante'
            ], 404);
        }

        $convocatorias = Documento::with([
            'archivo',
            'tipos_documento',
            'grupos_escalafon',
            'resultados.archivo',
            'resultados.tipos_documento'
        ])
            ->where('tipo_documento_id', $tipoConvocatoria->id)
            ->whereYear('fecha_publicacion', $request->anio)
            ->whereNull('documento_padre_id')
            ->where('publicado', true)
            ->orderBy('id', 'asc')
            ->get();

        return response()->json([
            'ok' => true,
            'message' => 'Convocatorias obtenidas correctamente',
            'data' => $convocatorias
        ], 200);
    }

    public function obtenerPorTipoYAnio(Request $request)
    {
        $request->validate([
            'tipo_documento' => 'required|string',
            'anio' => 'nullable|integer',
        ]);

        $tipoDocumento = TiposDocumento::whereRaw('LOWER(tipo_documento) = ?', [strtolower(trim($request->tipo_documento))])
            ->first();

        if (!$tipoDocumento) {
            return response()->json([
                'ok' => false,
                'message' => 'No existe el tipo de documento solicitado'
            ], 404);
        }

        $query = Documento::with(['archivo', 'tipos_documento', 'grupos_escalafon'])
            ->where('tipo_documento_id', $tipoDocumento->id)
            ->where('publicado', true);

        if ($request->filled('anio')) {
            $query->whereYear('fecha_publicacion', $request->anio);
        }

        $documentos = $query->orderBy('fecha_publicacion', 'desc')->get();

        return response()->json([
            'ok' => true,
            'message' => 'Documentos obtenidos correctamente',
            'data' => $documentos
        ], 200);
    }
    public function obtenerPorAnio(Request $request)
    {
        $request->validate([
            'anio' => 'required|integer',
        ]);

        $anio = (int) $request->anio;

        $documentos = Documento::with([
            'archivo',
            'tipos_documento',
            'grupos_escalafon',
            'resultados.archivo',
            'resultados.tipos_documento',
            'resultados.grupos_escalafon'
        ])
            ->whereYear('fecha_publicacion', $anio)
            ->whereNull('documento_padre_id')
            ->where('publicado', true)
            ->orderBy('fecha_publicacion', 'desc')
            ->get();

        $agrupados = $documentos
            ->groupBy(function ($doc) {
                return $doc->tipos_documento->tipo_documento ?? 'Sin tipo';
            })
            ->map(function ($items, $tipo) {
                return [
                    'tipo_documento' => $tipo,
                    'total' => $items->count(),
                    'documentos' => $items->map(function ($doc) {
                        return [
                            'id' => $doc->id,
                            'titulo' => $doc->titulo,
                            'descripcion' => $doc->descripcion,
                            'fecha_publicacion' => $doc->fecha_publicacion,
                            'anio' => $doc->anio,
                            'grupo' => $doc->grupos_escalafon ? [
                                'id' => $doc->grupos_escalafon->id,
                                'descripcion' => $doc->grupos_escalafon->descripcion,
                            ] : null,
                            'tipo_documento' => $doc->tipos_documento ? [
                                'id' => $doc->tipos_documento->id,
                                'descripcion' => $doc->tipos_documento->tipo_documento,
                            ] : null,
                            'archivo' => $doc->archivo ? [
                                'id' => $doc->archivo->id,
                                'nombre_original' => $doc->archivo->nombre_original,
                                'nombre_guardado' => $doc->archivo->nombre_guardado,
                                'url_publica' => $doc->archivo->url_publica,
                                'extension' => $doc->archivo->extension,
                                'tipo_mime' => $doc->archivo->tipo_mime,
                            ] : null,
                            'archivos_relacionados' => $doc->resultados->map(function ($hijo) {
                                return [
                                    'id' => $hijo->id,
                                    'titulo' => $hijo->titulo,
                                    'descripcion' => $hijo->descripcion,
                                    'fecha_publicacion' => $hijo->fecha_publicacion,
                                    'tipo_documento' => $hijo->tipos_documento ? [
                                        'id' => $hijo->tipos_documento->id,
                                        'descripcion' => $hijo->tipos_documento->tipo_documento,
                                    ] : null,
                                    'grupo' => $hijo->grupos_escalafon ? [
                                        'id' => $hijo->grupos_escalafon->id,
                                        'descripcion' => $hijo->grupos_escalafon->descripcion,
                                    ] : null,
                                    'archivo' => $hijo->archivo ? [
                                        'id' => $hijo->archivo->id,
                                        'nombre_original' => $hijo->archivo->nombre_original,
                                        'nombre_guardado' => $hijo->archivo->nombre_guardado,
                                        'url_publica' => $hijo->archivo->url_publica,
                                        'extension' => $hijo->archivo->extension,
                                        'tipo_mime' => $hijo->archivo->tipo_mime,
                                    ] : null,
                                ];
                            })->values(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        $aniosDisponibles = Documento::selectRaw('EXTRACT(YEAR FROM fecha_publicacion) as anio')
            ->whereNotNull('fecha_publicacion')
            ->where('publicado', true)
            ->distinct()
            ->orderBy('anio', 'desc')
            ->pluck('anio')
            ->map(fn($item) => (int) $item)
            ->values();

        $conteoPorTipo = Documento::with('tipos_documento')
            ->whereYear('fecha_publicacion', $anio)
            ->where('publicado', true)
            ->get()
            ->groupBy(function ($doc) {
                return $doc->tipos_documento->tipo_documento ?? 'Sin tipo';
            })
            ->map(function ($items, $tipo) {
                return [
                    'tipo_documento' => $tipo,
                    'total' => $items->count(),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'message' => 'Documentos obtenidos correctamente por año',
            'metadata' => [
                'anio_consultado' => $anio,
                'anios_disponibles' => $aniosDisponibles,
                'conteo_por_tipo' => $conteoPorTipo,
                'total_documentos_padre' => $documentos->count(),
            ],
            'data' => $agrupados
        ], 200);
    }
}
