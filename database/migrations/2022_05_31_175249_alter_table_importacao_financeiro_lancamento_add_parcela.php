<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoFinanceiroLancamentoAddParcela extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->integer('parcela')->nullable()->default(0);
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
            $table->dropColumn('parcela');
        });
    }
}
