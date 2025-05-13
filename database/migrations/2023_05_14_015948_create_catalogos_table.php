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
        Schema::create('catalogos', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('producto_netsuite_id');
            $table->string('producto');
            $table->string('ean');
            $table->string('tipo');
            $table->bigInteger('lista_precio_id');
            $table->string('linea')->nullable();
            $table->float('precio_venta')->default(0);
            $table->string('tasa_iva');
            $table->integer('iva')->default(0);
            $table->float('precion_con_iva')->default(0);
            $table->integer('cantidad_maxima')->default(0);
            $table->integer('cantidad_minima')->default(0);
            $table->integer('embalaje')->default(0);
            $table->integer('es_lote')->default(0);
            $table->integer('es_controlado')->default(0);
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
        Schema::dropIfExists('catalogos');
    }
};
