<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoFinanceiroPrevistoAddPrevisto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_financeiro_lancamentos', function (Blueprint $table) {
            $table->boolean('previsto')->nullable()->default(false);
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
            $table->dropColumn('previsto');
        });
    }
}
