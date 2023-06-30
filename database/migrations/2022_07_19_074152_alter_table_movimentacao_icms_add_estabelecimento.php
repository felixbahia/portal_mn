<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMovimentacaoIcmsAddEstabelecimento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimentacao_icms_armazem', function (Blueprint $table) {
            $table->string('estabelecimento_codigo', 2)->nullable();
        });
        Schema::table('movimentacao_valor_armazem', function (Blueprint $table) {
            $table->string('estabelecimento_codigo', 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimentacao_icms_armazem', function (Blueprint $table) {
            $table->dropColumn('estabelecimento_codigo');
        });
        Schema::table('movimentacao_valor_armazem', function (Blueprint $table) {
            $table->dropColumn('estabelecimento_codigo');
        });
    }
}
