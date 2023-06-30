<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteredTableImportacaoFinanceiroLancamentosAddDataBaixa extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->date('baixa_data')->nullable();
            $table->integer('importacao_financeiro_lancamentos_id_baixa')->nullable();

            $table->foreign('importacao_financeiro_lancamentos_id_baixa')
                ->references('id')
                ->on('importacao_financeiro_lancamentos')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->dropColumn('baixa_data');
            $table->dropColumn('importacao_financeiro_lancamentos_id_baixa');
        });
    }
}
