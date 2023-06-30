<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaCarrinhoDeCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('carrinho_de_compras', function (Blueprint $table) {
            $table->string('produto_codigo')->nullable()->change();
            $table->integer('pedido_item_id')->nullable()->change();
            $table->float('produto_quantidade')->nullable()->change();
            $table->float('produto_preco')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('carrinho_de_compras', function (Blueprint $table) {
            $table->string('produto_codigo')->change();
            $table->integer('pedido_item_id')->change();
            $table->float('produto_quantidade')->change();
            $table->float('produto_preco')->change();
        });
    }
}
