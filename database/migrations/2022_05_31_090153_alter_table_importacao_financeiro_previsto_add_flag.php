<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoFinanceiroPrevistoAddFlag extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_financeiro_previstos', function (Blueprint $table) {
            $table->boolean('pago')->nullable()->default(false);
        });

        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->boolean('pago')->nullable()->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importacao_financeiro_previstos', function (Blueprint $table) {
            $table->dropColumn('pago');
        });

        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->dropColumn('pago');
        });
    }
}
