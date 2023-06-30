<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAprovacaoRenegociacaoMotivosParaAprovacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_renegociacaos', function (Blueprint $table) {
            $table->boolean('motivo_desconto_no_valor_do_titulo')->default(false);
            $table->boolean('motivo_juros_baixo_permitido')->default(false);
            $table->boolean('motivo_periodo_maior_permitido')->default(false);
            $table->boolean('motivo_parcela_com_valor_fixo')->default(false);
            $table->boolean('motivo_sem_fiador')->default(false);
            $table->boolean('motivo_fiador_casado_sem_venia_conjugal')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aprovacao_renegociacaos', function (Blueprint $table) {
            $table->dropColumn('motivo_desconto_no_valor_do_titulo');
            $table->dropColumn('motivo_juros_baixo_permitido');
            $table->dropColumn('motivo_periodo_maior_permitido');
            $table->dropColumn('motivo_parcela_com_valor_fixo');
            $table->dropColumn('motivo_sem_fiador');
            $table->dropColumn('motivo_fiador_casado_sem_venia_conjugal');
        });
    }
}
