<?php

use App\Http\Controllers\Api\Escalafon\Catalogos\TipoDocumentosController;
use App\Http\Controllers\Api\Escalafon\documentos\DocumentoController;
use App\Http\Controllers\Api\prensa\boletines\BoletinesArchivoController;
use App\Http\Controllers\Api\prensa\boletines\BoletinesController;
use App\Http\Controllers\Api\Prensa\Catalogos\ConvocatoriaCatalogosController;
use App\Http\Controllers\Api\prensa\convocatorias\ConvocatoriaController;
use Illuminate\Support\Facades\Route;

Route::post('/convocatorias', [ConvocatoriaController::class, 'store']);
Route::delete('/convocatorias/{id}', [ConvocatoriaController::class, 'destroy']);
Route::post('/convocatorias/{id}', [ConvocatoriaController::class, 'update']);
Route::get('/convocatorias', [ConvocatoriaController::class, 'index']);

// rutas de los catalogos
Route::get('prensa/catalogos/convocatorias-fechas', [ConvocatoriaCatalogosController::class, 'index']);
Route::get('escalafon/tipos-documentos/{modulo}', [TipoDocumentosController::class, 'obtenerPorModulo']);
Route::get('escalafon/grupos-escalafon', [TipoDocumentosController::class, 'obtenerGruposEscalafon']);

// ruta de los boletines 
Route::get('prensa/boletines', [BoletinesController::class, 'index']);
Route::post('prensa/boletines', [BoletinesController::class, 'store']);
Route::put('prensa/boletines/{id}', [BoletinesController::class, 'update']);
Route::delete('prensa/boletines/{id}', [BoletinesController::class, 'destroy']);

// ruta de archivos de boletines
Route::post('prensa/boletines/archivos', [BoletinesArchivoController::class, 'store']);
Route::delete('prensa/boletines/archivos/{archivoId}', [BoletinesArchivoController::class, 'destroy']);


// rutas de documentos de escalafon
Route::post('escalafon/documentos', [DocumentoController::class, 'store']);
Route::get('escalafon/documentos/convocatorias-con-resultado', [DocumentoController::class, 'obtenerConvocatoriasConResultado']);
Route::get('escalafon/documentos/boletines', [DocumentoController::class, 'obtenerBoletines']);
Route::get('escalafon/documentos/convocatorias-vacantes', [DocumentoController::class, 'obtenerConvocatoriasVacantes']);
Route::get('escalafon/documentos/por-tipo', [DocumentoController::class, 'obtenerPorTipoYAnio']);
Route::get('escalafon/documentos/por-anio', [DocumentoController::class, 'obtenerPorAnio']);