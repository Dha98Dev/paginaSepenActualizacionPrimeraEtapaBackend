<?php

namespace App\Http\Controllers\Api\prensa\boletines;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\Boletines;
use App\Models\BoletinesArchivo;
use DB;
use Illuminate\Http\Request;
use Storage;
use Str;
use Validator;

class BoletinesArchivoController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'boletin_id' => ['required', 'integer', 'exists:boletines,id'],
            'imagenes' => ['required', 'array', 'min:1', 'max:10'],
            'imagenes.*' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'boletin_id.required' => 'El boletín es obligatorio.',
            'boletin_id.exists' => 'El boletín no existe.',
            'imagenes.required' => 'Debes enviar al menos una imagen.',
            'imagenes.array' => 'Las imágenes deben enviarse en un arreglo.',
            'imagenes.min' => 'Debes enviar al menos una imagen.',
            'imagenes.max' => 'Solo se permiten máximo 10 imágenes.',
            'imagenes.*.image' => 'Todos los archivos deben ser imágenes.',
            'imagenes.*.mimes' => 'Solo se permiten imágenes jpg, jpeg, png o webp.',
            'imagenes.*.max' => 'Cada imagen debe pesar máximo 5 MB.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'ok' => false,
                'message' => 'Errores de validación.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $boletin = Boletines::find($request->boletin_id);

        if (!$boletin) {
            return response()->json([
                'ok' => false,
                'message' => 'El boletín no existe.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            $archivosGuardados = [];

            foreach ($request->file('imagenes') as $imagen) {
                $nombreOriginal = $imagen->getClientOriginalName();
                $extension = $imagen->getClientOriginalExtension();
                $mime = $imagen->getMimeType();
                $tamano = $imagen->getSize();

                $nombreGuardado = now()->format('Ymd_His') . '_' . Str::random(10) . '.' . $extension;
                $ruta = $imagen->storeAs('boletines', $nombreGuardado, 'public');

                $archivo = Archivo::create([
                    'nombre_original' => $nombreOriginal,
                    'nombre_guardado' => $nombreGuardado,
                    'ruta' => $ruta,
                    'url_publica' => Storage::url($ruta),
                    'tipo_mime' => $mime,
                    'extension' => $extension,
                    'tamano_bytes' => $tamano,
                    'descripcion' => 'Imagen del boletín',
                    'estado' => 'ACTIVO',
                    'es_publico' => true,
                ]);

                $boletinArchivo = BoletinesArchivo::create([
                    'archivo_id' => $archivo->id,
                    'boletin_id' => $boletin->id,
                    'enlace_externo' => null,
                ]);

                $archivosGuardados[] = [
                    'id' => $boletinArchivo->id,
                    'archivo_id' => $archivo->id,
                    'boletin_id' => $boletin->id,
                    'nombre_original' => $archivo->nombre_original,
                    'ruta' => $archivo->ruta,
                    'url_publica' => $archivo->url_publica,
                ];
            }

            DB::commit();

            return response()->json([
                'ok' => true,
                'message' => 'Imágenes registradas correctamente.',
                'boletin_id' => $boletin->id,
                'total_archivos' => count($archivosGuardados),
                'archivos' => $archivosGuardados,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'ok' => false,
                'message' => 'Error al guardar las imágenes del boletín.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function destroy($archivoId)
    {
        try {
            $boletinArchivo = BoletinesArchivo::with('archivo')
                ->where('archivo_id', $archivoId)
                ->first();

            if (!$boletinArchivo) {
                return response()->json([
                    'ok' => false,
                    'message' => 'No se encontró relación para el archivo indicado.'
                ], 404);
            }

            $archivo = $boletinArchivo->archivo;

            // Eliminar archivo físico del storage
            if ($archivo && $archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
                Storage::disk('public')->delete($archivo->ruta);
            }

            // Guardar datos antes de borrar por si quieres retornarlos
            $boletinId = $boletinArchivo->boletin_id;
            $boletinArchivoId = $boletinArchivo->id;

            // Eliminar relación boletin_archivo
            $boletinArchivo->delete();

            // Eliminar registro del archivo
            if ($archivo) {
                $archivo->delete();
            }

            return response()->json([
                'ok' => true,
                'message' => 'Archivo eliminado correctamente.',
                'data' => [
                    'archivo_id' => (int) $archivoId,
                    'boletin_archivo_id' => $boletinArchivoId,
                    'boletin_id' => $boletinId,
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Error al eliminar el archivo.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
