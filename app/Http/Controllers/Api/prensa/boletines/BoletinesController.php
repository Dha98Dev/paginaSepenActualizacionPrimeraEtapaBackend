<?php

namespace App\Http\Controllers\Api\prensa\boletines;

use App\Http\Controllers\Controller;
use App\Models\Boletines;
use App\Models\BoletinesArchivo;
use DB;
use Illuminate\Http\Request;
use Storage;
use Validator;

class BoletinesController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'anio' => 'required|digits:4',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        $anio = (int) $request->anio;
        $mes = (int) $request->mes;

        $boletines = Boletines::with([
            'archivos.archivo'
        ])
            ->whereYear('fecha_evento', $anio)
            ->whereMonth('fecha_evento', $mes)
            ->orderByDesc('fecha_evento')
            ->orderByDesc('id')
            ->get()
            ->map(function ($boletin) {
                $enlacesExternos = [];
                $archivosFisicos = [];

                foreach ($boletin->archivos as $item) {
                    if (!empty($item->enlace_externo)) {
                        $enlacesExternos[] = [
                            'id' => $item->id,
                            'enlace_externo' => $item->enlace_externo,
                        ];
                    }

                    if (!empty($item->archivo_id) && $item->archivo) {
                        $archivosFisicos[] = [
                            'id' => $item->archivo->id,
                            'nombre_original' => $item->archivo->nombre_original,
                            'nombre_guardado' => $item->archivo->nombre_guardado,
                            'ruta' => $item->archivo->ruta,
                            'url_publica' => $item->archivo->url_publica ?? null,
                            'tipo_mime' => $item->archivo->tipo_mime,
                            'extension' => $item->archivo->extension,
                            'tamano_bytes' => $item->archivo->tamano_bytes,
                            'descripcion' => $item->archivo->descripcion,
                            'estado' => $item->archivo->estado,
                            'es_publico' => $item->archivo->es_publico,
                        ];
                    }
                }

                return [
                    'id' => $boletin->id,
                    'titulo' => $boletin->titulo,
                    'fecha_evento' => $boletin->fecha_evento,
                    'resumen' => $boletin->resumen,
                    'nota' => $boletin->nota,
                    'created_at' => $boletin->created_at,
                    'updated_at' => $boletin->updated_at,
                    'id_anterior' => $boletin->id_anterior,
                    'enlaces_externos' => $enlacesExternos,
                    'archivos_fisicos' => $archivosFisicos,
                ];
            });

        return response()->json([
            'ok' => true,
            'boletines' => $boletines,
        ]);
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'titulo' => ['required', 'string', 'max:255'],
            'fecha_evento' => ['required', 'date'],
            'resumen' => ['nullable', 'string'],
            'nota' => ['required', 'string'],
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'fecha_evento.required' => 'La fecha del evento es obligatoria.',
            'fecha_evento.date' => 'La fecha del evento no es válida.',
            'nota.required' => 'La nota es obligatoria.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $boletin = Boletines::create([
                'titulo' => trim($request->titulo),
                'fecha_evento' => $request->fecha_evento,
                'resumen' => $request->resumen,
                'nota' => $request->nota,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Boletín registrado correctamente.',
                'id' => $boletin->id,
                'boletin' => $boletin,
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al registrar el boletín.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'fecha_evento' => 'required|date',
            'resumen' => 'nullable|string',
            'nota' => 'required|string',
        ], [
            'titulo.required' => 'El título es obligatorio.',
            'fecha_evento.required' => 'La fecha del evento es obligatoria.',
            'fecha_evento.date' => 'La fecha del evento no es válida.',
            'nota.required' => 'La nota es obligatoria.',
        ]);

        try {
            $boletin = Boletines::find($id);

            if (!$boletin) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Boletín no encontrado.',
                ], 404);
            }

            $boletin->update([
                'titulo' => $request->titulo,
                'fecha_evento' => $request->fecha_evento,
                'resumen' => $request->resumen,
                'nota' => $request->nota,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Boletín actualizado correctamente.',
                'data' => $boletin->fresh(),
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar el boletín.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
public function destroy($id)
{
    DB::beginTransaction();

    try {
        $boletin = Boletines::find($id);

        if (!$boletin) {
            return response()->json([
                'ok' => false,
                'message' => 'Boletín no encontrado.'
            ], 404);
        }

        $boletinesArchivos = BoletinesArchivo::with('archivo')
            ->where('boletin_id', $boletin->id)
            ->get();

        foreach ($boletinesArchivos as $boletinArchivo) {
            $archivo = $boletinArchivo->archivo;

            // 1. borrar relación primero
            $boletinArchivo->delete();

            // 2. borrar archivo físico y registro
            if ($archivo) {
                if ($archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
                    Storage::disk('public')->delete($archivo->ruta);
                }

                $archivo->delete();
            }
        }

        // 3. borrar boletín
        $boletin->delete();

        DB::commit();

        return response()->json([
            'ok' => true,
            'message' => 'Boletín y sus archivos fueron eliminados correctamente.'
        ], 200);

    } catch (\Throwable $e) {
        DB::rollBack();

        return response()->json([
            'ok' => false,
            'message' => 'Error al eliminar el boletín.',
            'error' => $e->getMessage()
        ], 500);
    }
}
}
