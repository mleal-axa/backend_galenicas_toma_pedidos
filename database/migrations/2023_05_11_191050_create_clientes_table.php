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
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();

            $table->integer('netsuite_id');
            $table->string('tipo');
            $table->string('nombre');
            $table->string('nombre_compania')->nullable();
            $table->string('documento');
            $table->string('correo_electronico')->nullable();
            $table->string('telefono')->nullable();
            $table->float('cupo')->nullable();
            $table->float('cupo_feria')->nullable();
            $table->bigInteger('categoria_id')->nullable();
            $table->string('categoria')->nullable();
            $table->integer('mp_credito')->nullable();
            $table->integer('mp_contado')->nullable();
            $table->bigInteger('lista_precio_id')->nullable();
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
        Schema::dropIfExists('clientes');
    }
};
