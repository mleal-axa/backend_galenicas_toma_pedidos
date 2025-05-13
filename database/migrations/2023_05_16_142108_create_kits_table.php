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
        Schema::create('kits', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('netsuite_id');
            $table->string('nombre');
            $table->string('ean');
            $table->string('linea');
            $table->string('centro_de_costo');
            $table->bigInteger('componente_id');
            $table->string('componente');
            $table->integer('cantidad_kit')->default(0);
            $table->integer('cantidad_regular')->default(0);
            $table->integer('cantidad_regalo')->default(0);
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
        Schema::dropIfExists('kits');
    }
};
