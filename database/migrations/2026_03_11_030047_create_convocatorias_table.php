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
        Schema::create('convocatorias', function (Blueprint $table) {
            $table->bigInteger('id')->primary();
            $table->string('lugar', 150);
            $table->text('convocatoria')->nullable();
            $table->text('descripcion')->nullable();
            $table->date('fecha_evento')->nullable();
            $table->date('fecha_publicacion');
            $table->date('fecha_vencimiento');
            $table->boolean('publicar')->default(false);
            $table->boolean('vigente')->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->string('enlace_externo', 250)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('convocatorias');
    }
};
