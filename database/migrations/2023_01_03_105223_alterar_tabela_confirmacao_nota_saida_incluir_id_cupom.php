<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaConfirmacaoNotaSaidaIncluirIdCupom extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('confirmacao_notas_de_saida', function (Blueprint $table){
            $table->string('id_cupom',100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('confirmacao_notas_de_saida', function (Blueprint $table){
            $table->dropColumn('id_cupom');
        });
    }
}
