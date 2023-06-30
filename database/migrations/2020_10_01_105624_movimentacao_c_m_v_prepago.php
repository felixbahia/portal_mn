<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MovimentacaoCMVPrepago extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->float('custo_prepago')->nullable()->default('0');
            $table->float('custo_sem_imposto_prepago')->nullable()->default('0');
            $table->float('custo_pcmn_prepago')->nullable()->default('0');
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
            $table->dropColumn('custo_prepago');
            $table->dropColumn('custo_sem_imposto_prepago');
            $table->dropColumn('custo_pcmn_prepago');
        });
    }
}
