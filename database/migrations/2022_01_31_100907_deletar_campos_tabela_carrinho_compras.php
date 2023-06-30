<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeletarCamposTabelaCarrinhoCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('carrinho_compras', function (Blueprint $table) {
            $table->dropColumn('pedido_item_id');
            $table->dropColumn('cliente_codigo');
            $table->dropColumn('produto_codigo');
            $table->dropColumn('produto_quantidade');
            $table->dropColumn('produto_preco');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('carrinho_compras', function (Blueprint $table) {
            $table->integer('pedido_item_id');
            $table->string('cliente_codigo');
            $table->string('produto_codigo');
            $table->float('produto_quantidade');
            $table->float('produto_preco');
        });
    }
}
