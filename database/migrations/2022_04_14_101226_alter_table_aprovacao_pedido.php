<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAprovacaoPedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_de_pedidos', function (Blueprint $table) {
            $table->boolean('cliente_novo_distancia')->nullable();
            $table->boolean('cliente_com_atraso_e_credito')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aprovacao_de_pedidos', function (Blueprint $table) {
            $table->dropColumn('cliente_novo_distancia');
            $table->dropColumn('cliente_com_atraso_e_credito');
        });
    }
}
