<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicaoDeParametroDeAprovacaoDePedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('parametros_aprovacao_pedido', function ($table) {
            $table->integer('maximo_de_limite_credito')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('parametros_aprovacao_pedido', function ($table) {
            $table->dropColumn('maximo_de_limite_credito')->nullable();
        });
    }
}
