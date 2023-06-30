<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidoUsarCreditoAddCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_usar_credito_historico_liberacoes', function (Blueprint $table) {
            $table->float('pedido_aberto_valor')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_usar_credito_historico_liberacoes', function (Blueprint $table) {
            $table->dropColumn('pedido_aberto_valor');
        });
    }
}
