<?php

namespace App\Http\Controllers\Api\Prensa\Catalogos;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class ConvocatoriaCatalogosController extends Controller
{
    public function index()
    {
        try {

            $mesesNombre = [
                1  => 'Enero',
                2  => 'Febrero',
                3  => 'Marzo',
                4  => 'Abril',
                5  => 'Mayo',
                6  => 'Junio',
                7  => 'Julio',
                8  => 'Agosto',
                9  => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre'
            ];

            // Obtener meses desde fecha_publicacion
            $mesesDB = DB::table('convocatorias')
                ->selectRaw('EXTRACT(MONTH FROM fecha_publicacion) as mes')
                ->distinct()
                ->orderBy('mes')
                ->pluck('mes');

            $meses = [];

            foreach ($mesesDB as $mes) {
                $mes = (int) $mes;

                if ($mes >= 1 && $mes <= 12) {
                    $meses[] = [
                        'label' => $mesesNombre[$mes],
                        'value' => $mes
                    ];
                }
            }

            // Obtener años desde fecha_publicacion
            $aniosDB = DB::table('convocatorias')
                ->selectRaw('EXTRACT(YEAR FROM fecha_publicacion) as anio')
                ->distinct()
                ->orderByDesc('anio')
                ->pluck('anio');

            $anios = [];

            foreach ($aniosDB as $anio) {
                $anios[] = [
                    'label' => (int) $anio,
                    'value' => (int) $anio
                ];
            }

            return response()->json([
                'ok' => true,
                'data' => [
                    'meses' => $meses,
                    'anios' => $anios
                ]
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'ok' => false,
                'error' => 'Error al obtener catálogos de filtros',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}