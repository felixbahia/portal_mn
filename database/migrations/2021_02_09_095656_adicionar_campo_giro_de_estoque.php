<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCampoGiroDeEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('giro_de_estoques', function (Blueprint $table) {
            $table->integer('pedidos_venda');
            $table->float('vendas_aberto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giro_de_estoques', function (Blueprint $table) {
            $table->dropColumn('pedidos_venda');
            $table->dropColumn('vendas_aberto');
        });
    }
}
