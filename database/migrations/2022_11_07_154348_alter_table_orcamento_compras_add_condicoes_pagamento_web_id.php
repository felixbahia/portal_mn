<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOrcamentoComprasAddCondicoesPagamentoWebId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orcamento_compras', function (Blueprint $table) {
            $table->integer('condicoes_pagamento_web_id')->nullable();
            $table->integer('dia_fluxo_inicial')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orcamento_compras', function (Blueprint $table) {
            $table->dropColumn('competencia_orcamento_compras_id');
            $table->dropColumn('dia_fluxo_inicial');
        });
    }
}
