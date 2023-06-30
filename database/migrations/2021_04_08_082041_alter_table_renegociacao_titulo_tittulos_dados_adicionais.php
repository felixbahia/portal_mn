<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociacaoTituloTittulosDadosAdicionais extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_titulos', function (Blueprint $table) {
            $table->integer('parcela')->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->float('valor_original')->nullable();
            $table->float('valor_juros')->nullable();
            $table->float('valor_saldo')->nullable();
            $table->uuid('nota_id')->nullable();
            $table->string('nota_numero')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_titulos', function (Blueprint $table) {
            $table->dropColumn('parcela');
            $table->dropColumn('data_emissao');
            $table->dropColumn('data_vencimento');
            $table->dropColumn('valor_original');
            $table->dropColumn('valor_juros');
            $table->dropColumn('valor_saldo');
            $table->dropColumn('nota_id');
            $table->dropColumn('nota_numero');
        });
    }
}
