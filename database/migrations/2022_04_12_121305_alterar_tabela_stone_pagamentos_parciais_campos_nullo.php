<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaStonePagamentosParciaisCamposNullo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->string('parcelamento')->nullable()->change();
            $table->string('codigo_autoriazacao')->nullable()->change();
            $table->string('documento_cartao')->nullable()->change();
            $table->string('cnpj_operadora')->nullable()->change();
            $table->string('contrato_cartao')->nullable()->change();
            $table->string('meio_eletronico')->nullable()->change();
            $table->string('operadora')->nullable()->change();
            $table->string('bandeira')->nullable()->change();
            $table->string('id_pagamento_nasajon')->nullable()->change();
            $table->string('retorno_api_atualizar_cartao_nasajon')->nullable()->change();
            $table->integer('tipo_operacao')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->string('parcelamento')->change();
            $table->string('codigo_autoriazacao')->change();
            $table->string('documento_cartao')->change();
            $table->string('cnpj_operadora')->change();
            $table->string('contrato_cartao')->nullable()->change();
            $table->string('meio_eletronico')->change();
            $table->string('operadora')->change();
            $table->string('bandeira')->change();
            $table->string('id_pagamento_nasajon')->change();
            $table->string('retorno_api_atualizar_cartao_nasajon')->change();
            $table->integer('tipo_operacao')->change();
        });
    }
}
