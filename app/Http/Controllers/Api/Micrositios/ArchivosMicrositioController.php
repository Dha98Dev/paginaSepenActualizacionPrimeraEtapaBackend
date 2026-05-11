<?php

namespace App\Http\Controllers\Api\Micrositios;

use App\Http\Controllers\Controller;
use App\Models\Archivo;
use App\Models\ArchivosMicrositio;
use App\Models\Micrositio;
use Auth;
use Illuminate\Http\Request;
use Storage;
use Str;

class ArchivosMicrositioController extends Controller
{
    public function guardarArchivo(Request $request, int $micrositioId)
{
    $request->validate([
        'archivo' => 'required|file|max:20480',
        'descripcion' => 'nullable|string|max:500',
        'es_publico' => 'nullable|boolean',
    ]);

    $micrositio = Micrositio::find($micrositioId);

    if (!$micrositio) {
        return response()->json([
            'ok' => false,
            'message' => 'Micrositio no encontrado.',
        ], 404);
    }

    $file = $request->file('archivo');

    $nombreOriginal = $file->getClientOriginalName();
    $extension = strtolower($file->getClientOriginalExtension());
    $tipoMime = $file->getMimeType();
    $tamanoBytes = $file->getSize();

    $nombreSinExtension = pathinfo($nombreOriginal, PATHINFO_FILENAME);

    $nombreGuardado = Str::slug($nombreSinExtension)
        . '-'
        . now()->format('YmdHis')
        . '-'
        . Str::random(8)
        . '.'
        . $extension;

    $rutaCarpeta = 'micrositios/' . $micrositio->id;

    $ruta = $file->storeAs(
        $rutaCarpeta,
        $nombreGuardado,
        'public'
    );

    $hashArchivo = hash_file('sha256', $file->getRealPath());

    $archivo = Archivo::create([
        'nombre_original' => $nombreOriginal,
        'nombre_guardado' => $nombreGuardado,
        'ruta' => $ruta,
        'tipo_mime' => $tipoMime,
        'extension' => $extension,
        'tamano_bytes' => $tamanoBytes,
        'hash_archivo' => $hashArchivo,
        'descripcion' => $request->descripcion,
        'es_publico' => $request->boolean('es_publico', true),
        'estado' => 'ACTIVO',
        'creado_por' => Auth::id(),
    ]);

    ArchivosMicrositio::create([
        'archivo_id' => $archivo->id,
        'micrositio_id' => $micrositio->id,
    ]);

return response()->json([
    'ok' => true,
    'message' => 'Archivo guardado correctamente.',
    'data' => [
        'micrositio' => [
            'id' => $micrositio->id,
            'nombre' => $micrositio->nombre,
            'slug' => $micrositio->slug,
        ],
        'archivo' => [
            'id' => $archivo->id,
            'nombre_original' => $archivo->nombre_original,
            'nombre_guardado' => $archivo->nombre_guardado,
            'ruta' => $archivo->ruta,
            'url_publica' => Storage::disk('public')->url($archivo->ruta),
            'tipo_mime' => $archivo->tipo_mime,
            'extension' => $archivo->extension,
            'tamano_bytes' => $archivo->tamano_bytes,
        ],
    ],
], 201);
}
public function eliminarArchivoPorUrl(Request $request)
{
    $validated = $request->validate([
        'url_publica' => 'required|string',
    ]);

    $urlPublica = $validated['url_publica'];

    $path = parse_url($urlPublica, PHP_URL_PATH);

    if (!$path) {
        return response()->json([
            'ok' => false,
            'message' => 'La URL proporcionada no es válida.',
        ], 422);
    }

    $ruta = ltrim($path, '/');

    if (str_starts_with($ruta, 'storage/')) {
        $ruta = substr($ruta, strlen('storage/'));
    }

    $archivo = Archivo::where('ruta', $ruta)->first();

    if (!$archivo) {
        return response()->json([
            'ok' => false,
            'message' => 'No se encontró un archivo con esa URL.',
            'url_publica' => $urlPublica,
            'ruta_detectada' => $ruta,
        ], 404);
    }

    ArchivosMicrositio::where('archivo_id', $archivo->id)->delete();

    if ($archivo->ruta && Storage::disk('public')->exists($archivo->ruta)) {
        Storage::disk('public')->delete($archivo->ruta);
    }

    $archivo->delete();

    return response()->json([
        'ok' => true,
        'message' => 'Archivo eliminado correctamente.',
        'data' => [
            'archivo_id' => $archivo->id,
            'ruta' => $ruta,
        ],
    ]);
}
}
