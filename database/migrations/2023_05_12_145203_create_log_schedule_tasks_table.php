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
        Schema::create('log_schedule_tasks', function (Blueprint $table) {
            $table->id();

            $table->string('proceso');
            $table->string('tabla');
            $table->integer('error')->default(0);
            $table->integer('cant_registro')->default(0);
            $table->integer('cant_insertados')->default(0);
            $table->integer('cant_actualizados')->default(0);
            $table->date('fecha')->nullable();
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->string('tiempo')->nullable();
            $table->text('mensaje_error')->nullable();

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
        Schema::dropIfExists('log_schedule_tasks');
    }
};
