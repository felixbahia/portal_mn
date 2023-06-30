<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionaParametroExpiracaoVisualizacaoDePedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parametros_pedido', function ($table) {
            $table->integer('expiracao_visulizacao')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parametros_pedido', function ($table) {
            $table->drop('expiracao_visulizacao');
        });
    }
}
