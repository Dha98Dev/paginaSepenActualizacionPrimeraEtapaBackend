<?php

namespace App\Http\Controllers\Api\Documentos;

use App\Http\Controllers\Controller;
use App\Models\Modulo;
use Illuminate\Http\Request;
use App\Models\Archivo;
use App\Models\Documento;
use App\Models\TiposDocumento;
use Carbon\Carbon;
use DB;
use Storage;
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
            'archivo' => 'required|file|max:100480',
            'fecha_publicacion' => 'required|date',
            'modulo' => 'required|string|max:255',
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

            $directorio = "{$request->modulo}/{$slugTipoDocumento}/{$anio}";
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
    public function obtenerHistoricoCatalogosYProyectos()
    {
        $tipos = TiposDocumento::whereIn(DB::raw('UPPER(tipo_documento)'), ['PROYECTO', 'CATALOGO'])
            ->get()
            ->keyBy(function ($item) {
                return strtoupper($item->tipo_documento);
            });

        if (!isset($tipos['PROYECTO']) || !isset($tipos['CATALOGO'])) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontraron los tipos de documento PROYECTO y/o CATALOGO'
            ], 404);
        }

        $documentos = Documento::with([
            'archivo',
            'tipos_documento',
            'grupos_escalafon'
        ])
            ->whereIn('tipo_documento_id', [
                $tipos['PROYECTO']->id,
                $tipos['CATALOGO']->id
            ])
            ->whereNull('documento_padre_id')
            ->where('publicado', true)
            ->orderBy('fecha_publicacion', 'desc')
            ->get();

        $registros = $documentos
            ->groupBy(function ($doc) {
                $anio = $doc->fecha_publicacion
                    ? $doc->fecha_publicacion->format('Y')
                    : ($doc->anio ?? 'sin_anio');

                $grupoId = $doc->grupo_id ?? 'sin_grupo';

                return $anio . '_' . $grupoId;
            })
            ->map(function ($items) {
                $primero = $items->first();

                $anio = $primero->fecha_publicacion
                    ? (int) $primero->fecha_publicacion->format('Y')
                    : (int) $primero->anio;

                $grupo = $primero->grupos_escalafon ? [
                    'id' => $primero->grupos_escalafon->id,
                    'descripcion' => $primero->grupos_escalafon->descripcion,
                ] : null;

                $proyecto = $items->first(function ($doc) {
                    return strtoupper($doc->tipos_documento->tipo_documento ?? '') === 'PROYECTO';
                });

                $catalogo = $items->first(function ($doc) {
                    return strtoupper($doc->tipos_documento->tipo_documento ?? '') === 'CATALOGO';
                });

                return [
                    'anio' => $anio,
                    'grupo' => $grupo,
                    'proyecto' => $proyecto ? [
                        'id' => $proyecto->id,
                        'titulo' => $proyecto->titulo,
                        'descripcion' => $proyecto->descripcion,
                        'fecha_publicacion' => $proyecto->fecha_publicacion,
                        'archivo' => $proyecto->archivo ? [
                            'id' => $proyecto->archivo->id,
                            'nombre_original' => $proyecto->archivo->nombre_original,
                            'nombre_guardado' => $proyecto->archivo->nombre_guardado,
                            'url_publica' => $proyecto->archivo->url_publica,
                            'extension' => $proyecto->archivo->extension,
                            'tipo_mime' => $proyecto->archivo->tipo_mime,
                            'tamano_bytes' => $proyecto->archivo->tamano_bytes,
                        ] : null,
                    ] : null,
                    'catalogo' => $catalogo ? [
                        'id' => $catalogo->id,
                        'titulo' => $catalogo->titulo,
                        'descripcion' => $catalogo->descripcion,
                        'fecha_publicacion' => $catalogo->fecha_publicacion,
                        'archivo' => $catalogo->archivo ? [
                            'id' => $catalogo->archivo->id,
                            'nombre_original' => $catalogo->archivo->nombre_original,
                            'nombre_guardado' => $catalogo->archivo->nombre_guardado,
                            'url_publica' => $catalogo->archivo->url_publica,
                            'extension' => $catalogo->archivo->extension,
                            'tipo_mime' => $catalogo->archivo->tipo_mime,
                            'tamano_bytes' => $catalogo->archivo->tamano_bytes,
                        ] : null,
                    ] : null,
                ];
            })
            ->sortByDesc('anio')
            ->values();

        $dataAgrupada = $registros
            ->groupBy('anio')
            ->map(function ($items, $anio) {
                return [
                    'anio' => (int) $anio,
                    'registros' => $items->sortBy(function ($item) {
                        return $item['grupo']['descripcion'] ?? '';
                    })->values(),
                ];
            })
            ->sortByDesc('anio')
            ->values();

        $aniosDisponibles = $dataAgrupada
            ->pluck('anio')
            ->values();

        return response()->json([
            'ok' => true,
            'message' => 'Histórico de proyectos y catálogos obtenido correctamente',
            'metadata' => [
                'anios_disponibles' => $aniosDisponibles,
                'total_anios' => $dataAgrupada->count(),
                'total_registros' => $registros->count(),
            ],
            'data' => $dataAgrupada
        ], 200);
    }

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

        $documentos = Documento::with([
            'archivo',
            'tipos_documento',
            'grupos_escalafon',
            'resultados.archivo',
            'resultados.tipos_documento',
            'resultados.grupos_escalafon'
        ])
            ->whereHas('tipos_documento', function ($query) use ($moduloEncontrado) {
                $query->where('modulo_id', $moduloEncontrado->id);
            })
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
                            'anio' => $doc->anio,
                            'fecha_publicacion' => $doc->fecha_publicacion,
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
                                'tamano_bytes' => $doc->archivo->tamano_bytes,
                            ] : null,
                            'archivos_relacionados' => $doc->resultados->map(function ($resultado) {
                                return [
                                    'id' => $resultado->id,
                                    'titulo' => $resultado->titulo,
                                    'descripcion' => $resultado->descripcion,
                                    'anio' => $resultado->anio,
                                    'fecha_publicacion' => $resultado->fecha_publicacion,
                                    'grupo' => $resultado->grupos_escalafon ? [
                                        'id' => $resultado->grupos_escalafon->id,
                                        'descripcion' => $resultado->grupos_escalafon->descripcion,
                                    ] : null,
                                    'tipo_documento' => $resultado->tipos_documento ? [
                                        'id' => $resultado->tipos_documento->id,
                                        'descripcion' => $resultado->tipos_documento->tipo_documento,
                                    ] : null,
                                    'archivo' => $resultado->archivo ? [
                                        'id' => $resultado->archivo->id,
                                        'nombre_original' => $resultado->archivo->nombre_original,
                                        'nombre_guardado' => $resultado->archivo->nombre_guardado,
                                        'url_publica' => $resultado->archivo->url_publica,
                                        'extension' => $resultado->archivo->extension,
                                        'tipo_mime' => $resultado->archivo->tipo_mime,
                                        'tamano_bytes' => $resultado->archivo->tamano_bytes,
                                    ] : null,
                                ];
                            })->values(),
                            'numero_archivos_relacionados' => $doc->resultados->count(),
                        ];
                    })->values(),
                ];
            })
            ->values();

        return response()->json([
            'ok' => true,
            'message' => 'Documentos obtenidos correctamente',
            'modulo' => [
                'id' => $moduloEncontrado->id,
                'descripcion' => $moduloEncontrado->descripcion
            ],
            'metadata' => [
                'total_tipos_documento' => $agrupados->count(),
                'total_documentos' => $documentos->count(),
            ],
            'data' => $agrupados
        ], 200);
    }

    public function actualizarDocumento(Request $request, int $id)
    {
        $request->validate([
            'tipo_documento_id' => 'required|integer|exists:tipos_documentos,id',
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'fecha_publicacion' => 'required|date',
            'grupo_id' => 'nullable|integer|exists:grupos_escalafon,id',
            'documento_padre_id' => 'nullable|integer|exists:documentos,id',
            'archivo' => 'nullable|file|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $documento = Documento::with(['archivo', 'tipos_documento'])->find($id);

            if (!$documento) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Documento no encontrado'
                ], 404);
            }

            $documento->update([
                'tipo_documento_id' => $request->tipo_documento_id,
                'titulo' => trim($request->titulo),
                'descripcion' => $request->descripcion ? trim($request->descripcion) : null,
                'grupo_id' => $request->filled('grupo_id') ? $request->grupo_id : null,
                'documento_padre_id' => $request->filled('documento_padre_id') ? $request->documento_padre_id : null,
                'fecha_publicacion' => $request->fecha_publicacion,
            ]);

            if ($request->hasFile('archivo')) {
                $archivoAnterior = $documento->archivo;

                $tipoDocumento = TiposDocumento::find($request->tipo_documento_id);

                if (!$tipoDocumento) {
                    DB::rollBack();
                    return response()->json([
                        'ok' => false,
                        'message' => 'Tipo de documento no encontrado'
                    ], 404);
                }

                $nombreCarpeta = Str::slug($tipoDocumento->tipo_documento);
                $anio = Carbon::parse($request->fecha_publicacion)->format('Y');

                $archivoNuevo = $request->file('archivo');
                $nombreOriginal = $archivoNuevo->getClientOriginalName();
                $extension = strtolower($archivoNuevo->getClientOriginalExtension());

                $nombreBase = pathinfo($nombreOriginal, PATHINFO_FILENAME);
                $nombreGuardado = time() . '_' . Str::slug($nombreBase) . '.' . $extension;

                $rutaRelativa = $archivoNuevo->storeAs(
                    "imagen/{$nombreCarpeta}/{$anio}",
                    $nombreGuardado,
                    'public'
                );

                if ($archivoAnterior) {
                    if ($archivoAnterior->ruta && Storage::disk('public')->exists($archivoAnterior->ruta)) {
                        Storage::disk('public')->delete($archivoAnterior->ruta);
                    }

                    $archivoAnterior->update([
                        'nombre_original' => $nombreOriginal,
                        'nombre_guardado' => $nombreGuardado,
                        'ruta' => $rutaRelativa,
                        'tipo_mime' => $archivoNuevo->getMimeType(),
                        'extension' => $extension,
                        'tamano_bytes' => $archivoNuevo->getSize(),
                    ]);
                } else {
                    $nuevoArchivo = Archivo::create([
                        'nombre_original' => $nombreOriginal,
                        'nombre_guardado' => $nombreGuardado,
                        'ruta' => $rutaRelativa,
                        'tipo_mime' => $archivoNuevo->getMimeType(),
                        'extension' => $extension,
                        'tamano_bytes' => $archivoNuevo->getSize(),
                    ]);

                    $documento->archivo_id = $nuevoArchivo->id;
                    $documento->save();
                }
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Documento actualizado correctamente',
                'data' => Documento::with(['archivo', 'tipos_documento', 'grupos_escalafon', 'resultados.archivo'])->find($documento->id)
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar el documento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $documento = Documento::with(['archivo', 'resultados'])->find($id);

            if (!$documento) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Documento no encontrado'
                ], 404);
            }

            if ($documento->resultados && $documento->resultados->count() > 0) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se puede eliminar el documento porque tiene archivos o resultados relacionados'
                ], 409);
            }

            $rutaArchivo = $documento->archivo?->ruta;
            $archivoId = $documento->archivo?->id;

            $documento->delete();

            if ($archivoId) {
                Archivo::where('id', $archivoId)->delete();
            }

            if ($rutaArchivo && Storage::disk('public')->exists($rutaArchivo)) {
                Storage::disk('public')->delete($rutaArchivo);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Documento eliminado correctamente'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar el documento',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
