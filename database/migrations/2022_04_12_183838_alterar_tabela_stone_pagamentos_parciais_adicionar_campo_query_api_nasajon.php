<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaStonePagamentosParciaisAdicionarCampoQueryApiNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->text('query_api_nasajon')->nullable();
        });

        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->text('query_api_nasajon')->nullable();
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
            $table->dropColumn('query_api_nasajon');
        });

        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->dropColumn('query_api_nasajon');
        });
    }
}
