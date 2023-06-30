<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCampoDataAtivacaoProdutoEspecificacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produto_especificacaos', function (Blueprint $table) {
            $table->date('data_ativacao')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produto_especificacaos', function (Blueprint $table) {
            $table->dropColumn('data_ativacao');

        });
    }
}
