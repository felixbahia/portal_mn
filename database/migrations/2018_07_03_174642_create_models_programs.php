<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateModelsPrograms extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome');
            $table->string('url');
            $table->string('icon')->nullable(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('sub_modulos', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('modulos_id');
            $table->string('nome');
            $table->string('url');
            $table->string('icon')->nullable(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('programas', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('modulos_id')->nullable(true);
            $table->unsignedInteger('sub_modulos_id')->nullable(true);
            $table->string('nome');
            $table->string('icon')->nullable(true);
            $table->string('model');
            $table->string('route_index');
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::table('sub_modulos', function (Blueprint $table) {
            $table->foreign('modulos_id')
                ->references('id')
                ->on('modulos')
                ->onDelete('cascade');
        });
        Schema::table('programas', function (Blueprint $table) {
            $table->foreign('modulos_id')
                ->references('id')
                ->on('modulos')
                ->onDelete('cascade');
            $table->foreign('sub_modulos_id')
                ->references('id')
                ->on('sub_modulos')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programas');
        Schema::dropIfExists('sub_mobulos');
        Schema::dropIfExists('modulos');
    }
}
