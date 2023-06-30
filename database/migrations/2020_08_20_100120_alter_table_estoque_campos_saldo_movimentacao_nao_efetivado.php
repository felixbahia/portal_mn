<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstoqueCamposSaldoMovimentacaoNaoEfetivado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produtos_estoques', function ($table) {
            $table->float('saldo_movimento_nao_efetivado')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produtos_estoques', function ($table) {
            $table->dropColumn('saldo_movimento_nao_efetivado');
        });
    }
}
