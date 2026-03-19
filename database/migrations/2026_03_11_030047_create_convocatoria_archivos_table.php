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
        Schema::create('convocatoria_archivos', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->bigInteger('convocatoria_id');
            $table->bigInteger('archivo_id');
            $table->string('tipo_relacion', 50)->nullable()->default('PRINCIPAL');
            $table->integer('orden')->nullable()->default(1);
            $table->boolean('principal')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['convocatoria_id', 'archivo_id'], 'uq_convocatoria_archivo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convocatoria_archivos');
    }
};
