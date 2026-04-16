<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Documentos\DocumentoController;
use App\Http\Controllers\Api\Escalafon\Catalogos\TipoDocumentosController;
use App\Http\Controllers\Api\prensa\boletines\BoletinesArchivoController;
use App\Http\Controllers\Api\prensa\boletines\BoletinesController;
use App\Http\Controllers\Api\Prensa\Catalogos\ConvocatoriaCatalogosController;
use App\Http\Controllers\Api\Prensa\Catalogos\ModulosCatalogoController;
use App\Http\Controllers\Api\Prensa\contenidoTextual\ContenidoTextoModuloController;
use App\Http\Controllers\Api\prensa\convocatorias\ConvocatoriaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas públicas
|--------------------------------------------------------------------------
*/

// Convocatorias
Route::get('/convocatorias', [ConvocatoriaController::class, 'index']);

// Catálogos
Route::get('prensa/catalogos/convocatorias-fechas', [ConvocatoriaCatalogosController::class, 'index']);
Route::get('escalafon/tipos-documentos/{modulo}', [TipoDocumentosController::class, 'obtenerPorModulo']);
Route::get('escalafon/grupos-escalafon', [TipoDocumentosController::class, 'obtenerGruposEscalafon']);
Route::get('prensa/catalogos/modulos', [ModulosCatalogoController::class, 'index']);

// Boletines
Route::get('prensa/boletines', [BoletinesController::class, 'index']);

// Documentos
Route::get('documentos/convocatorias-con-resultado', [DocumentoController::class, 'obtenerConvocatoriasConResultado']);
Route::get('documentos/boletines', [DocumentoController::class, 'obtenerBoletines']);
Route::get('documentos/convocatorias-vacantes', [DocumentoController::class, 'obtenerConvocatoriasVacantes']);
Route::get('documentos/por-tipo', [DocumentoController::class, 'obtenerPorTipoYAnio']);
Route::get('documentos/por-anio', [DocumentoController::class, 'obtenerPorAnio']);
Route::get('documentos/historico-catalogos-proyectos', [DocumentoController::class, 'obtenerHistoricoCatalogosYProyectos']);
Route::get('documentos/modulo/{modulo}', [DocumentoController::class, 'obtenerPorModulo']);


/*
|--------------------------------------------------------------------------
| Rutas protegidas
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/convocatorias', [ConvocatoriaController::class, 'store'])->middleware('permiso:prensa.convocatorias.crear');

    Route::post('/convocatorias/{id}', [ConvocatoriaController::class, 'update'])->middleware('permiso:prensa.convocatorias.editar');

    Route::delete('/convocatorias/{id}', [ConvocatoriaController::class, 'destroy'])->middleware('permiso:prensa.convocatorias.eliminar');

    Route::post('prensa/boletines', [BoletinesController::class, 'store'])->middleware('permiso:prensa.comunicados.crear');

    Route::put('prensa/boletines/{id}', [BoletinesController::class, 'update'])->middleware('permiso:prensa.comunicados.editar');

    Route::delete('prensa/boletines/{id}', [BoletinesController::class, 'destroy'])->middleware('permiso:prensa.comunicados.eliminar');

    Route::post('prensa/boletines/archivos', [BoletinesArchivoController::class, 'store'])->middleware('permiso:prensa.comunicados.crear');

    Route::delete('prensa/boletines/archivos/{archivoId}', [BoletinesArchivoController::class, 'destroy'])->middleware('permiso:prensa.comunicados.eliminar');

    
    // ruta de documentos
    Route::post('documentos', [DocumentoController::class, 'store']);
    Route::post('documentos/{id}/actualizar', [DocumentoController::class, 'actualizarDocumento']);
    Route::delete('documentos/{id}', [DocumentoController::class, 'destroy']);
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});