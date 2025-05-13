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
        Schema::create('detalles_inventarios', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('item_id');
            $table->string('ean');
            $table->string('nombre_item');
            $table->bigInteger('id_lote')->nullable();
            $table->string('lote')->nullable();
            $table->bigInteger('id_bin')->nullable();
            $table->string('bin')->nullable();
            $table->bigInteger('ubicacion_id')->nullable();
            $table->string('ubicacion')->nullable();
            $table->string('estado')->nullable();
            $table->integer('saldo')->default(0);
            $table->integer('disponible')->default(0);
            $table->string('es_producto_numerado_por_lote')->nullable();
            $table->date('fecha_vencimiento')->nullable();
            $table->string('inactivo')->nullable();

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
        Schema::dropIfExists('detalles_inventarios');
    }
};
