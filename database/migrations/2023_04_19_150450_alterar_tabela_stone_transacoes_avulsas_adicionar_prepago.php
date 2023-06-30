<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaStoneTransacoesAvulsasAdicionarPrepago extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_retorno_transacoes_avulsas', function ($table) {
            $table->string('pre_pago')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_retorno_transacoes_avulsas', function ($table) {
            $table->dropColumn('pre_pago');
        });
    }
}
