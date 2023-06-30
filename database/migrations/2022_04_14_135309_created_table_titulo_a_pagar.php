<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableTituloAPagar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulo_a_pagar', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo');
            $table->string('titulo_numero');
            $table->integer('titulo_parcela');
            $table->string('titulo_tipo');
            $table->string('titulo_situacao');
            $table->date('titulo_emissao');
            $table->date('titulo_vencimento');
            $table->date('titulo_data_baixa');
            $table->float('titulo_valor');
            $table->float('titulo_valor_liquido');
            $table->float('titulo_valor_baixa');
            $table->float('titulo_valor_saldo_adiantamento');
            $table->string('fornecedor_codigo');
            $table->string('fornecedor_nome');
            $table->string('fornecedor_razao_social');
            $table->string('tipo');
            $table->integer('condicao_pagamento_periodo');
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
        Schema::dropIfExists('titulo_a_pagar');
    }
}
