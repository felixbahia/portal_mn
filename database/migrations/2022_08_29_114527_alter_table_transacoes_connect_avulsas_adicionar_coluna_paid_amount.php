<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTransacoesConnectAvulsasAdicionarColunaPaidAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_retorno_transacoes_avulsas', function (Blueprint $table) {
            $table->string('data_paid_amount')->nullable();     
            $table->string('order_closed')->nullable();     
            $table->integer('order_amount')->nullable();     
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
            $table->dropColumn('data_paid_amount');
            $table->dropColumn('order_closed');
            $table->dropColumn('order_amount');
        });
    }
}
