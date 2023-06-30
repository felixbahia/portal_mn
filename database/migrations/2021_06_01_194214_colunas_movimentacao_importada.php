<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ColunasMovimentacaoImportada extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->float('valor_outras_despesas')->nullable();
            $table->float('valor_pis')->nullable();
            $table->float('valor_cofins')->nullable();
            $table->float('valor_aframm')->nullable();
            $table->float('valor_2')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->dropColumn('valor_outras_despesas');
            $table->dropColumn('valor_pis');
            $table->dropColumn('valor_cofins');
            $table->dropColumn('valor_aframm');
            $table->dropColumn('valor_2');
        });
    }
}
