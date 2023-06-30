<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ColunasFaturamentoOnlineDivisaoValores extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('faturamento_online', function (Blueprint $table) {
            $table->float('valor_produto_nacional')->nullable();
            $table->float('valor_produto_importado')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('faturamento_online', function (Blueprint $table) {
            $table->dropColumn('valor_produto_nacional');
            $table->dropColumn('valor_produto_importado');
        });
    }
}
