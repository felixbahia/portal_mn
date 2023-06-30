<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarCampoRespostaScoreFornecedores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('score_fornecedores_formulario_respondidos', function (Blueprint $table) {
            $table->string('resposta')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('score_fornecedores_formulario_respondidos', function (Blueprint $table) {
            $table->string('resposta')->change();
        });
    }
}
