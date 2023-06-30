<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidoComprasAbertoTituloAddNaoLancado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_compras_aberto_titulo_futuros', function (Blueprint $table) {
            $table->boolean('nao_lancado')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_compras_aberto_titulo_futuros', function (Blueprint $table) {
            $table->dropColumn('nao_lancado');
        });
    }
}
