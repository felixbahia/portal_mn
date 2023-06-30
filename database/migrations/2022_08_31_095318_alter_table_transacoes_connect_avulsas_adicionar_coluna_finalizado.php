<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTransacoesConnectAvulsasAdicionarColunaFinalizado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_retorno_transacoes_avulsas', function (Blueprint $table) {
            $table->boolean('finalizado')->nullable();    
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_retorno_transacoes_avulsas', function (Blueprint $table) {
            $table->dropColumn('finalizado');
        });
    }
}
