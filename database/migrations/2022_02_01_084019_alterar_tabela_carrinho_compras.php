<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaCarrinhoCompras extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('carrinho_compras', function (Blueprint $table) {
            $table->integer('dados_cliente_pedido_id');
            $table->string('transportadora_codigo');
            $table->string('transportadora_redespacho')->nullable();

            $table->foreign('dados_cliente_pedido_id')
                ->references('id')
                ->on('dados_cliente_pedidos')
                ->onDelete('NO ACTION');
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
            $table->dropColumn('dados_cliente_pedido_id');
            $table->dropColumn('transportadora_codigo');
            $table->dropColumn('transportadora_redespacho');
        });
    }
}
