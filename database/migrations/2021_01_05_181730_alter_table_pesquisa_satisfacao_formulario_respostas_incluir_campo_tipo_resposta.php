<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePesquisaSatisfacaoFormularioRespostasIncluirCampoTipoResposta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pesquisa_satisfacao_formulario_respostas', function (Blueprint $table) {
            $table->integer('pesquisa_satisfacao_formulario_tipo_respostas_id')->nullable();

            $table->foreign('pesquisa_satisfacao_formulario_tipo_respostas_id')
                ->references('id')
                ->on('pesquisa_satisfacao_formulario_tipo_respostas')
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
        Schema::table('pesquisa_satisfacao_formulario_respostas', function (Blueprint $table) {
            $table->dropForeign('pesquisa_satisfacao_formulario_tipo_respostas_id');
            $table->dropColumn('pesquisa_satisfacao_formulario_tipo_respostas_id');
        });
    }
}
