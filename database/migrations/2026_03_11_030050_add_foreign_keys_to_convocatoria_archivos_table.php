<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('convocatoria_archivos', function (Blueprint $table) {
            $table->foreign(['archivo_id'], 'fk_convocatoria_archivos_archivo')->references(['id'])->on('archivos')->onUpdate('no action')->onDelete('cascade');
            $table->foreign(['convocatoria_id'], 'fk_convocatoria_archivos_convocatoria')->references(['id'])->on('convocatorias')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('convocatoria_archivos', function (Blueprint $table) {
            $table->dropForeign('fk_convocatoria_archivos_archivo');
            $table->dropForeign('fk_convocatoria_archivos_convocatoria');
        });
    }
};
