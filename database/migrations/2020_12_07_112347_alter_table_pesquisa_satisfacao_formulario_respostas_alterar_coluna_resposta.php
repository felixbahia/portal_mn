<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePesquisaSatisfacaoFormularioRespostasAlterarColunaResposta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pesquisa_satisfacao_formulario_respostas', function (Blueprint $table) {
            $table->renameColumn('resposta','respostas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pesquisa_satisfacao_formulario_respostas', function (Blueprint $table) {
            $table->renameColumn('respostas','resposta');
        });
    }
}
