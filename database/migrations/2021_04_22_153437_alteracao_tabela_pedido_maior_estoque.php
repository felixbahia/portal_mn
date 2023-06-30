<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteracaoTabelaPedidoMaiorEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_maior_estoques', function (Blueprint $table) {
            $table->renameColumn('codigo_produto','produto_codigo');
            $table->renameColumn('descricao','produto_descricao');
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
            $table->renameColumn('produto_codigo', 'codigo_produto');
            $table->renameColumn('produto_descricao','descricao');
        });
    }
}
