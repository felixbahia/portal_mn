<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeletarCampoTabelaDadosClientePedidos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dados_cliente_pedidos', function (Blueprint $table) {
            $table->dropColumn('transportadora_codigo');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('dados_cliente_pedidos', function (Blueprint $table) {
            $table->string('transportadora_codigo');
        });
    }
}
