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
        Schema::create('archivos', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre_original');
            $table->string('nombre_guardado');
            $table->string('ruta', 500);
            $table->string('url_publica', 500)->nullable();
            $table->string('tipo_mime', 150)->nullable();
            $table->string('extension', 20)->nullable();
            $table->bigInteger('tamano_bytes')->nullable();
            $table->string('hash_archivo')->nullable();
            $table->text('descripcion')->nullable();
            $table->boolean('es_publico')->nullable()->default(true);
            $table->enum('estado', ['ACTIVO', 'ELIMINADO'])->nullable()->default('ACTIVO');
            $table->bigInteger('creado_por')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archivos');
    }
};
