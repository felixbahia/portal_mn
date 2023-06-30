<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaCarrinhoDeCompraClientes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dados_cliente_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cliente_codigo');
            $table->string('transportadora_codigo');
            $table->string('tipo_frete');
            $table->string('condicao_pagamento');
            $table->date('data_previsao_entrega');
            $table->string('transportadora_redespacho_codigo')->nullable();
            $table->string('tipo_frete_redespacho')->nullable();
            $table->float('valor_frete')->nullable();
            $table->float('valor_frete_redespacho')->nullable();
            $table->string('nome_contato')->nullable();
            $table->string('email_contato')->nullable();
            $table->string('no_pedido_compra')->nullable();
            $table->string('cliente_telefone')->nullable();
            $table->string('tipo_venda');
            $table->boolean('metragem_exata')->default(false);
            $table->boolean('bater_amostra')->default(false);
            $table->boolean('incluir_cartelas')->default(false);
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('dados_cliente_pedidos');
    }
}
