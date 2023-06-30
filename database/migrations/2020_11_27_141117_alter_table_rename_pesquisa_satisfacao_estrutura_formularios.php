<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenamePesquisaSatisfacaoEstruturaFormularios extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('pesquisa_satisfacao_estrutura_formularios','pesquisa_satisfacao_formularios');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('pesquisa_satisfacao_formularios','pesquisa_satisfacao_estrutura_formularios');
    }
}
