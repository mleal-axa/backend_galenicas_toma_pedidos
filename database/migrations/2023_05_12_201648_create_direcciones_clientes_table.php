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
        Schema::create('direcciones_clientes', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('cliente_netsuite_id');
            $table->integer('netsuite_id');
            $table->string('nombre_sucursal');
            $table->text('direccion_completa');
            $table->string('direccion_corta');
            $table->string('barrio')->nullable();
            $table->string('pais')->nullable();
            $table->string('departamento')->nullable();
            $table->string('ciudad')->nullable();
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
        Schema::dropIfExists('direcciones_clientes');
    }
};
