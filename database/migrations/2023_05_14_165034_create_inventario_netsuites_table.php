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
        Schema::create('inventario_netsuites', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('producto_netsuite_id');
            $table->string('producto');
            $table->string('ean');
            $table->bigInteger('id_ubicacion');
            $table->string('ubicacion');
            $table->integer('disponible')->default(0);
            $table->string('es_producto_numerado_por_lote')->nullable();

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
        Schema::dropIfExists('inventario_netsuites');
    }
};
