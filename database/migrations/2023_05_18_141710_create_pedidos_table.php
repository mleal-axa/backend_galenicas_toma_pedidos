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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('netsuite_id')->nullable();
            $table->string('numero_factura')->nullable();
            $table->bigInteger('user_id');
            $table->bigInteger('cliente_id');
            $table->bigInteger('direccion_id');
            $table->bigInteger('ubicacion_id');
            $table->bigInteger('lista_precio_id');
            $table->date('fecha');
            $table->string('nota')->nullable();
            $table->bigInteger('transportadora_id')->nullable();
            $table->bigInteger('medio_pago_id')->nullable();
            $table->bigInteger('medico_id')->nullable();
            $table->integer('metodo_pago');
            $table->integer('estado')->default(1);
            $table->integer('cantidad_items')->default(0);
            $table->integer('cantidad_items_solicitado')->default(0);
            $table->integer('impuesto')->default(0);
            $table->integer('subtotal')->default(0);
            $table->integer('envio')->default(0);
            $table->integer('descuento')->default(0);
            $table->integer('total')->default(0);
            $table->bigInteger('user_id_canceled')->nullable();
            $table->bigInteger('user_id_updated')->nullable();
            $table->string('fecha_respuesta_netsuite')->nullable();

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
        Schema::dropIfExists('pedidos');
    }
};
