<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociacaoTarifaBancariaJurosAtualizacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->float('juro_atualizacao_titulo')->nullable();
            $table->float('tarifa_bancaria_atualizacao_titulo')->nullable();
            $table->float('tarifa_bancaria_renegociacao')->nullable();
            $table->date('data_inicial_renegociacao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->dropColumn('juro_atualizacao_titulo');
            $table->dropColumn('tarifa_bancaria_atualizacao_titulo');
            $table->dropColumn('tarifa_bancaria_renegociacao');
            $table->dropColumn('data_inicial_renegociacao');
        });
    }
}
