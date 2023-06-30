<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOrcamentoComprasAddCompetenciaIdQuantidadeParcelas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('orcamento_compras', function (Blueprint $table) {
            $table->integer('competencia_orcamento_compras_id')->nullable();
            $table->integer('parcela_quantidade')->nullable();
            $table->float('parcela_valor')->nullable();
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
            $table->dropColumn('parcela_quantidade');
            $table->dropColumn('parcela_valor');
        });
    }
}
