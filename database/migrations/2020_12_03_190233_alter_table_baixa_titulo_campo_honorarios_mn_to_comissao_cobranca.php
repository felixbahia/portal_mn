<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBaixaTituloCampoHonorariosMnToComissaoCobranca extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('baixa_titulos', function (Blueprint $table) {
            $table->renameColumn('honorarios_mn_valor','comissao_cobranca_valor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('baixa_titulos', function (Blueprint $table) {
            $table->renameColumn('comissao_cobranca_valor','honorarios_mn_valor');
        });
    }
}
