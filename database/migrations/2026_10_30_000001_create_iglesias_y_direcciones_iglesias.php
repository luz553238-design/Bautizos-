
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iglesias', function (Blueprint $table) {
            $table->id('id_iglesia');

            $table->string('nombre', 180);
            $table->string('tipo', 40)->default('Parroquia');
            $table->string('telefono', 20)->nullable();
            $table->string('correo', 150)->nullable();

            $table->boolean('activo')->default(true);

            $table->timestamps();
        });

        Schema::create('direcciones_iglesias', function (Blueprint $table) {
            $table->id('id_direccion_iglesia');

            $table->unsignedBigInteger('id_iglesia')->unique();

            $table->string('calle', 180);
            $table->string('numero_exterior', 20);
            $table->string('numero_interior', 20)->nullable();
            $table->string('colonia', 150);
            $table->string('codigo_postal', 5);
            $table->string('municipio', 120);
            $table->string('estado', 120);
            $table->text('referencias')->nullable();

            $table->timestamps();

            $table->foreign('id_iglesia')
                ->references('id_iglesia')
                ->on('iglesias')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direcciones_iglesias');
        Schema::dropIfExists('iglesias');
    }
};
