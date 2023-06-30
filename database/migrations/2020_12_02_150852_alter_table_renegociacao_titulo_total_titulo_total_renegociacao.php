<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociacaoTituloTotalTituloTotalRenegociacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->float('valor_total_titulos')->nullable();
            $table->float('valor_total_titulos_com_juros')->nullable();
            $table->string('email_previa')->nullable();
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
            $table->dropColumn('valor_total_titulos');
            $table->dropColumn('valor_total_titulos_com_juros');
            $table->dropColumn('email_previa');
        });
    }
}
