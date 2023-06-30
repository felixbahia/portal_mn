<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstoqueDadosAdicionais extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produtos_estoques', function (Blueprint $table) {
            $table->date('data_ultima_venda')->nullable();
            $table->float('venda_mes_1')->nullable();
            $table->float('venda_mes_2')->nullable();
            $table->float('venda_mes_3')->nullable();
            $table->float('venda_mes_4')->nullable();
            $table->float('venda_mes_5')->nullable();
            $table->float('venda_mes_6')->nullable();
            $table->float('venda_mes_7')->nullable();
            $table->float('venda_mes_8')->nullable();
            $table->float('venda_mes_9')->nullable();
            $table->float('venda_mes_10')->nullable();
            $table->float('venda_mes_11')->nullable();
            $table->float('venda_mes_12')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produtos_estoques', function (Blueprint $table) {
            $table->dropColumn('data_ultima_venda');
            $table->dropColumn('venda_mes_1');
            $table->dropColumn('venda_mes_2');
            $table->dropColumn('venda_mes_3');
            $table->dropColumn('venda_mes_4');
            $table->dropColumn('venda_mes_5');
            $table->dropColumn('venda_mes_6');
            $table->dropColumn('venda_mes_7');
            $table->dropColumn('venda_mes_8');
            $table->dropColumn('venda_mes_9');
            $table->dropColumn('venda_mes_10');
            $table->dropColumn('venda_mes_11');
            $table->dropColumn('venda_mes_12');
        });
    }
}
