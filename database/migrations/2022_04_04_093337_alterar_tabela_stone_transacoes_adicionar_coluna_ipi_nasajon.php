<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaStoneTransacoesAdicionarColunaIpiNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->boolean('api_nasajon')->nullable();
            $table->uuid('retorno_api_nasajon_pagamento')->nullable();
            $table->uuid('retorno_api_nasajon_cartao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->dropColumn('api_nasajon')->nullable();
            $table->dropColumn('retorno_api_nasajon_pagamento')->nullable();
            $table->dropColumn('retorno_api_nasajon_cartao')->nullable();
        });
    }
}
