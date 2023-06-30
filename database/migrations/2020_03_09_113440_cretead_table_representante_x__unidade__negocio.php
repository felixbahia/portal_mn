<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreteadTableRepresentanteXUnidadeNegocio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('unidade_negocio_metas_x_representantes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('unidade_negocio_metas_id');
            $table->integer('users_id');

            $table->softDeletes();
            $table->timestamps();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->foreign('unidade_negocio_metas_id')
                ->references('id')
                ->on('unidade_negocio_metas')
                ->onDelete('NO ACTION');
            $table->foreign('users_id')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('unidade_negocio_metas_x_representantes');
    }
}
