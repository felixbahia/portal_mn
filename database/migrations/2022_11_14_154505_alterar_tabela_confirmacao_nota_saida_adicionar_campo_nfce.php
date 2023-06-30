<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaConfirmacaoNotaSaidaAdicionarCampoNfce extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('confirmacao_notas_de_saida', function (Blueprint $table){
            $table->boolean('nfce')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('confirmacao_notas_de_saida', function (Blueprint $table) {
            $table->dropColumn('nfce');
        });
    }
}
