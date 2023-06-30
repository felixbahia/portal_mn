<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableStoneTransacoesPedidoAdicionarCampoTitulosExcluido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->boolean('titulos_excluidos')->nullable();  
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->dropColumn('titulos_excluidos');
        });
    }
}
