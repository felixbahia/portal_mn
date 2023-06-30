<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class NotasPorTransportadoraHeader extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_transportadora_headers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cnpj_transportadora')->nullable();
            $table->string('filial_emissora_documento')->nullable();
            $table->string('tipo_documento_cobranca')->nullable();
            $table->string('documento_cobranca_serie')->nullable();
            $table->string('documento_cobranca')->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->float('valor_total')->nullable();
            $table->string('tipo_cobranca')->nullable();
            $table->float('percentual_multa_atraso')->nullable();
            $table->float('valor_juros_dia_atraso')->nullable();
            $table->date('data_limite_pagamento_desconto')->nullable();
            $table->float('valor_desconto')->nullable();
            $table->string('codigo_banco')->nullable();
            $table->string('nome_banco')->nullable();
            $table->string('numero_agencia')->nullable();
            $table->string('agencia_digito')->nullable();
            $table->string('conta_corrente')->nullable();
            $table->string('conta_corrente_digito')->nullable();
            $table->string('acao_documento')->nullable();
            $table->string('identificacao_pre_fatura_cliente')->nullable();
            $table->string('identificacao_complementar_pre_fatura_cliente')->nullable();
            $table->string('cfop')->nullable();
            $table->string('chave_acesso_nf')->nullable();
            $table->string('chave_acesso_nf_com_dv')->nullable();
            $table->string('numero_protocolo_nf')->nullable();
            $table->float('valor_total_icms')->nullable();
            $table->float('aliquota_icms')->nullable();
            $table->float('base_calculo_icms')->nullable();
            $table->float('valor_total_iss')->nullable();
            $table->float('aliquota_iss')->nullable();
            $table->float('base_calculo_iss')->nullable();
            $table->float('valor_total_icms_st')->nullable();
            $table->float('aliquota_icms_st')->nullable();
            $table->float('base_calculo_icms_st')->nullable();
            $table->float('valor_total_ir')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notas_transportadora_headers');
    }
}