<?php

namespace App\Http\Controllers\Api\prensa\convocatorias;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Convocatoria;
use App\Models\ConvocatoriaArchivo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ConvocatoriaController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'anio' => 'required|digits:4',
            'mes' => 'required|integer|min:1|max:12',
        ]);

        $anio = (int) $request->anio;
        $mes = (int) $request->mes;
        $hoy = Carbon::today()->toDateString();

        DB::beginTransaction();

        try {
            Convocatoria::whereDate('fecha_vencimiento', '<', $hoy)
                ->update(['vigente' => false]);

            Convocatoria::whereDate('fecha_publicacion', '<=', $hoy)
                ->whereDate('fecha_vencimiento', '>', $hoy)
                ->where('publicar', true)
                ->update(['vigente' => true]);

            $convocatorias = Convocatoria::with([
                'archivos' => function ($query) {
                    $query->select(
                        'archivos.id',
                        'nombre_original',
                        'nombre_guardado',
                        'ruta',
                        'tipo_mime',
                        'extension',
                        'tamano_bytes',
                        'descripcion',
                        'estado',
                        'es_publico'
                    );
                }
            ])
                ->whereYear('fecha_publicacion', $anio)
                ->whereMonth('fecha_publicacion', $mes)
                ->orderByDesc('id')
                ->get();

            DB::commit();

            return response()->json([
                'ok' => true,
                'convocatorias' => $convocatorias,
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'error' => 'Error interno del servidor',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
    public function store(Request $request)
    {
        $request->validate([
            'lugar' => 'required|string|max:150',
            'convocatoria' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'fecha_evento' => 'required|date',
            'fecha_publicacion' => 'required|date',
            'fecha_vencimiento' => 'required|date',
            'publicar' => 'required|boolean',
            'vigente' => 'required|boolean',
            'enlace_externo' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        DB::beginTransaction();

        try {
            $convocatoria = Convocatoria::create([
                'lugar' => $request->lugar,
                'convocatoria' => $request->convocatoria,
                'descripcion' => $request->descripcion,
                'fecha_evento' => $request->fecha_evento,
                'fecha_publicacion' => $request->fecha_publicacion,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'enlace_externo' => $request->enlace_externo,
                'publicar' => $request->boolean('publicar'),
                'vigente' => $request->boolean('vigente'),
            ]);

            $archivoData = null;

            if ($request->hasFile('archivo')) {
                $file = $request->file('archivo');

                $nombreOriginal = $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                $tamanoBytes = $file->getSize();
                $hashArchivo = hash_file('sha256', $file->getRealPath());

                $nombreGuardado = now()->format('YmdHis') . '_' . Str::uuid() . '.' . $extension;

                $ruta = $file->storeAs('convocatorias', $nombreGuardado, 'public');

                $ruta = $file->storeAs('convocatorias', $nombreGuardado, 'public');

                $archivo = Archivo::create([
                    'nombre_original' => $nombreOriginal,
                    'nombre_guardado' => $nombreGuardado,
                    'ruta' => $ruta,
                    'tipo_mime' => $mimeType,
                    'extension' => $extension,
                    'tamano_bytes' => $tamanoBytes,
                    'hash_archivo' => $hashArchivo,
                    'descripcion' => 'Archivo adjunto de convocatoria',
                    'es_publico' => true,
                    'estado' => 'ACTIVO',
                ]);

                ConvocatoriaArchivo::create([
                    'convocatoria_id' => $convocatoria->id,
                    'archivo_id' => $archivo->id,
                    'tipo_relacion' => strtoupper($extension) === 'PDF' ? 'PDF' : 'ADJUNTO',
                    'orden' => 1,
                    'principal' => true,
                    'created_at' => now(),
                ]);

                $archivoData = $archivo;
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Convocatoria guardada correctamente',
                'data' => [
                    'convocatoria' => $convocatoria,
                    'archivo' => $archivoData,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al guardar la convocatoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'archivo' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
            'convocatoria' => 'nullable|string',
            'descripcion' => 'nullable|string',
            'eliminar_archivo' => 'nullable|boolean',
            'enlace_externo' => 'nullable|url|max:500',
            'fecha_evento' => 'required|date',
            'fecha_publicacion' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_publicacion',
            'lugar' => 'required|string|max:150',
            'publicar' => 'required|boolean',
            'vigente' => 'required|boolean',
        ]);

        DB::beginTransaction();

        try {
            $convocatoria = Convocatoria::findOrFail($id);

            $convocatoria->update([
                'lugar' => $request->lugar,
                'convocatoria' => $request->convocatoria,
                'descripcion' => $request->descripcion,
                'fecha_evento' => $request->fecha_evento,
                'fecha_publicacion' => $request->fecha_publicacion,
                'fecha_vencimiento' => $request->fecha_vencimiento,
                'publicar' => $request->boolean('publicar'),
                'vigente' => $request->boolean('vigente'),
                'enlace_externo' => $request->enlace_externo,
            ]);

            $relacionActual = ConvocatoriaArchivo::with('archivo')
                ->where('convocatoria_id', $convocatoria->id)
                ->where('principal', true)
                ->first();

            $quiereEliminarArchivo = $request->boolean('eliminar_archivo');
            $subioArchivoNuevo = $request->hasFile('archivo');

            if ($quiereEliminarArchivo || $subioArchivoNuevo) {
                if ($relacionActual) {
                    $archivoAnterior = $relacionActual->archivo;

                    if ($archivoAnterior) {
                        if (!empty($archivoAnterior->ruta)) {
                            Storage::disk('public')->delete($archivoAnterior->ruta);
                        }

                        $archivoAnterior->delete();
                    }

                    $relacionActual->delete();
                }
            }

            $archivoGuardado = null;

            if ($subioArchivoNuevo) {
                $file = $request->file('archivo');

                $nombreOriginal = $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $mimeType = $file->getMimeType();
                $tamanoBytes = $file->getSize();
                $hashArchivo = hash_file('sha256', $file->getRealPath());

                $nombreGuardado = now()->format('YmdHis') . '_' . Str::uuid() . '.' . $extension;
                $ruta = $file->storeAs('convocatorias', $nombreGuardado, 'public');

                $archivoGuardado = Archivo::create([
                    'nombre_original' => $nombreOriginal,
                    'nombre_guardado' => $nombreGuardado,
                    'ruta' => $ruta,
                    'tipo_mime' => $mimeType,
                    'extension' => $extension,
                    'tamano_bytes' => $tamanoBytes,
                    'hash_archivo' => $hashArchivo,
                    'descripcion' => 'Archivo actualizado de convocatoria',
                    'es_publico' => true,
                    'estado' => 'ACTIVO',
                    'creado_por' => null,
                ]);

                ConvocatoriaArchivo::create([
                    'convocatoria_id' => $convocatoria->id,
                    'archivo_id' => $archivoGuardado->id,
                    'tipo_relacion' => strtoupper($extension) === 'PDF' ? 'PDF' : 'ADJUNTO',
                    'orden' => 1,
                    'principal' => true,
                    'created_at' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Convocatoria actualizada correctamente',
                'data' => [
                    'convocatoria' => $convocatoria->fresh(),
                    'archivo' => $archivoGuardado,
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al actualizar la convocatoria',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $convocatoria = Convocatoria::findOrFail($id);

            $relaciones = ConvocatoriaArchivo::where('convocatoria_id', $id)->get();

            foreach ($relaciones as $relacion) {

                $archivo = Archivo::find($relacion->archivo_id);

                if ($archivo && $archivo->ruta) {

                    Storage::disk('public')->delete($archivo->ruta);

                    $archivo->delete();
                }

                $relacion->delete();
            }

            $convocatoria->delete();

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Convocatoria eliminada correctamente'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar convocatoria',
                'error' => $e->getMessage()
            ], 500);

        }
    }



}