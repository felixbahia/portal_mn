<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCampoTabelaDadosClientePedidos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('dados_cliente_pedidos', function (Blueprint $table) {
            $table->string('codigo_cliente_conta_e_ordem')->nullable();
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
            $table->dropColumn('codigo_cliente_conta_e_ordem');
        });
    }
}
