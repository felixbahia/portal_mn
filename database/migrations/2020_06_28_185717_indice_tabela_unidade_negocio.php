<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IndiceTabelaUnidadeNegocio extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function ($table) {
            $table->index(array('id', 'codigo_representante', 'deleted_at'));
        });
        Schema::table('unidade_negocio_metas_x_users', function ($table) {
            $table->index(array('id', 'users_id', 'unidade_negocio_metas_id', 'deleted_at'));
        });
        Schema::table('unidade_negocio_metas', function ($table) {
            $table->index(array('id', 'unidades_negocios_id', 'data', 'deleted_at'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function ($table) {
            $table->index(array('id'));
        });
        Schema::table('unidade_negocio_metas_x_users', function ($table) {
            $table->index(array('id'));
        });
        Schema::table('unidade_negocio_metas', function ($table) {
            $table->index(array('id'));
        });
    }
}
