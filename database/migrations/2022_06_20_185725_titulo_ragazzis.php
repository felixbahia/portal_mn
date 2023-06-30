<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TituloRagazzis extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulo_ragazzis', function (Blueprint $table) {
            $table->string('estabelecimento')->nullable();
            $table->string('cliente')->nullable();
            $table->string('numero_titulo')->nullable();
            $table->string('parcela_codigo')->nullable();
            $table->string('data_emissao')->nullable();
            $table->string('data_vencimento_atualizado')->nullable();
            $table->string('data_pagamento')->nullable();
            $table->string('data_lancamento')->nullable();
            $table->string('valor_titulo')->nullable();
            $table->string('valor_pago')->nullable();
            $table->string('valor_juro')->nullable();
            $table->string('valor_desconto')->nullable();
            $table->string('conta_agencia')->nullable();
            $table->string('conta')->nullable();
            $table->string('banco')->nullable();
            $table->string('observacao')->nullable();
            $table->string('renegociado')->nullable();
            $table->string('multa')->nullable();
            $table->string('taxa_boleto')->nullable();
            $table->string('honorarios_cliente')->nullable();
            $table->string('comissao_cobranca')->nullable();
            $table->string('tarifa_bancaria_mn')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('titulo_ragazzis');
    }
}
