<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();

            $table->string('netsuite_id');
            $table->string('nombre');
            $table->string('codigo');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('descuento')->default(0);
            $table->string('id_busqueda_items')->nullable();
            $table->string('nombre_busqueda_items')->nullable();
            $table->text('descripcion')->nullable();
            $table->text('ubicaciones')->nullable();
            $table->integer('isinactive')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('promociones');
    }
};
