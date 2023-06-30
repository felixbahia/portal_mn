<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePesquisaSatisfacaoFormularioAdicionarRelacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pesquisa_satisfacao_formularios', function (Blueprint $table) {
            $table->renameColumn('tipo_resposta','pesquisa_satisfacao_formulario_tipo_respostas_id');
        });

        Schema::table('pesquisa_satisfacao_formularios', function (Blueprint $table) {
            $table->foreign('pesquisa_satisfacao_formulario_tipo_respostas_id')
                ->references('id')
                ->on('pesquisa_satisfacao_formulario_tipo_respostas')
                ->onDelete('NO ACTION');
        });

        Schema::table('pesquisa_satisfacao_formulario_tipo_respostas', function (Blueprint $table) {
            $table->dropColumn('tipo_resposta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pesquisa_satisfacao_formularios', function (Blueprint $table) {
            $table->renameColumn('pesquisa_satisfacao_formulario_tipo_respostas_id','tipo_resposta');
        });

        Schema::table('pesquisa_satisfacao_formularios', function (Blueprint $table) {
            $table->dropForeign('pesquisa_satisfacao_formulario_tipo_respostas_id');
            $table->dropColumn('pesquisa_satisfacao_formulario_tipo_respostas_id');
        });

        Schema::table('pesquisa_satisfacao_formulario_tipo_respostas', function (Blueprint $table) {
            $table->integer('tipo_resposta');
        });
    }
}
