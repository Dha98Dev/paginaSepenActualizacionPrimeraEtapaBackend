<?php

namespace App\Http\Controllers\Api\Micrositios;

use App\Http\Controllers\Controller;
use App\Models\ArchivosMicrositio;
use Auth;
use Illuminate\Http\Request;

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
        'estado' => 'activo',
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
            'archivo' => $archivo,
        ],
    ], 201);
}
}
