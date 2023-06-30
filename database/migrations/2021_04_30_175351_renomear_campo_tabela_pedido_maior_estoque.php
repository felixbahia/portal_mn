<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenomearCampoTabelaPedidoMaiorEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_maior_estoques', function (Blueprint $table) {
            $table->renameColumn('compras_em_transito', 'estoque_em_transito');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_maior_estoques', function (Blueprint $table) {
            $table->renameColumn('estoque_em_transito', 'compras_em_transito');
        });
    }
}
