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
        Schema::create('detalles_pedidos', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('pedido_id');
            $table->bigInteger('producto_netsuite_id');
            $table->string('producto_nombre');
            $table->string('producto_ean');
            $table->string('tipo_producto');
            $table->integer('cantidad')->default(0);
            $table->integer('precio_unitario')->default(0);
            $table->integer('iva')->default(0);
            $table->integer('precio')->default(0);
            $table->bigInteger('promocion_netsuite_id')->nullable();
            $table->string('promocion')->nullable();
            $table->integer('porcentaje_descuento')->default(0);
            $table->integer('valor_descuento')->default(0);
            $table->integer('subtotal')->default(0);
            $table->integer('total')->default(0);
            $table->integer('es_lote')->default(0);
            $table->integer('active')->default(1);

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
        Schema::dropIfExists('detalles_pedidos');
    }
};
