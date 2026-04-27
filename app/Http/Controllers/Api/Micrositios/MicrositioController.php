<?php

namespace App\Http\Controllers\Api\Micrositios;

use App\Http\Controllers\Controller;
use App\Models\Micrositio;
use Auth;
use Illuminate\Http\Request;
use Str;

class MicrositioController extends Controller
{
      public function guardar(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'estructura' => 'array',
            'estatus' => 'nullable|string|in:borrador,publicado,archivado',
        ]);

        $slug = $validated['slug'] ?? Str::slug($validated['nombre']);

        $existeSlug = Micrositio::where('slug', $slug)->exists();

        if ($existeSlug) {
            return response()->json([
                'ok' => false,
                'message' => 'Ya existe un micrositio con este slug.',
                'slug' => $slug,
            ], 422);
        }

        $micrositio = Micrositio::create([
            'nombre' => $validated['nombre'],
            'slug' => $slug,
            'estructura' => $validated['estructura'],
            'estatus' => $validated['estatus'] ?? 'borrador',
            'creado_por' => Auth::id(),
            'actualizado_por' => Auth::id(),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Micrositio guardado correctamente.',
            'data' => $micrositio,
        ], 201);
    }

    public function buscarPorSlug(string $slug)
    {
        $micrositio = Micrositio::where('slug', $slug)
            ->whereNull('deleted_at')
            ->first();

        if (!$micrositio) {
            return response()->json([
                'ok' => false,
                'message' => 'No se encontró el micrositio solicitado.',
                'slug' => $slug,
            ], 404);
        }

        return response()->json([
            'ok' => true,
            'data' => $micrositio,
        ]);
    }
    public function listado(Request $request)
{
    $query = Micrositio::query();

    // Filtro opcional por estatus
    if ($request->has('estatus')) {
        $query->where('estatus', $request->estatus);
    }

    // Búsqueda por nombre o slug
    if ($request->has('buscar')) {
        $buscar = strtolower($request->buscar);

        $query->where(function ($q) use ($buscar) {
            $q->whereRaw('LOWER(nombre) LIKE ?', ["%{$buscar}%"])
              ->orWhereRaw('LOWER(slug) LIKE ?', ["%{$buscar}%"]);
        });
    }

    $micrositios = $query
        ->orderBy('created_at', 'desc')
        ->get();

    return response()->json([
        'ok' => true,
        'data' => $micrositios,
    ]);
}
public function actualizar(Request $request, int $id)
{
    $micrositio = Micrositio::find($id);

    if (!$micrositio) {
        return response()->json([
            'ok' => false,
            'message' => 'Micrositio no encontrado'
        ], 404);
    }

    $validated = $request->validate([
        'nombre' => 'required|string|max:255',
        'slug' => 'required|string|max:255',
        'estructura' => 'required|array',
        'estatus' => 'nullable|string|in:borrador,publicado,archivado',
    ]);

    // Validar que el slug no exista en otro registro
    $existeSlug = Micrositio::where('slug', $validated['slug'])
        ->where('id', '!=', $id)
        ->exists();

    if ($existeSlug) {
        return response()->json([
            'ok' => false,
            'message' => 'El slug ya está en uso'
        ], 422);
    }

    $micrositio->update([
        'nombre' => $validated['nombre'],
        'slug' => $validated['slug'],
        'estructura' => $validated['estructura'],
        'estatus' => $validated['estatus'] ?? $micrositio->estatus,
        'actualizado_por' => Auth::id(),
    ]);

    return response()->json([
        'ok' => true,
        'message' => 'Micrositio actualizado correctamente',
        'data' => $micrositio,
    ]);
}
public function publicar(int $id)
{
    $micrositio = Micrositio::findOrFail($id);

    $micrositio->update([
        'estatus' => 'publicado',
        'publicado_en' => now(),
        'publicado_por' => Auth::id(),
    ]);

    return response()->json([
        'ok' => true,
        'message' => 'Micrositio publicado'
    ]);
}
}
