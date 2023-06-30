<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTablePedidoComprasAbertoTitulo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_compras_aberto_titulo_futuros', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo', 2);
            $table->string('fornecedor_cnpj');
            $table->string('fornecedor_nome');
            $table->string('pedido_compras_numero');
            $table->uuid('pedido_compras_uuid');
            $table->date('pedido_compras_emissao');
            $table->date('pedido_compras_previsao_chegada');
            $table->string('pedido_compras_condicao_pagamento');
            $table->string('nota_entrada_numero');
            $table->string('forma_pagamento_descricao');
            $table->integer('parcela');
            $table->float('valor');
            $table->date('parcela_data');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pedido_compras_aberto_titulo_futuros');
    }
}
