<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarCadastroFasesDoProjeto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('status_projeto_exibicaos', 'status_projeto_exibicao');
        Schema::rename('status_projetos', 'status_projeto');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('status_projeto_exibicao', 'status_projeto_exibicaos');
        Schema::rename('status_projeto', 'status_projetos');
    }
}
