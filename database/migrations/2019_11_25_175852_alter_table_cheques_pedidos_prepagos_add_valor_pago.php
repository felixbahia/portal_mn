<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableChequesPedidosPrepagosAddValorPago extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('cheques_pedidos_prepagos', function ($table) {
            $table->float('valor_pago');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
        Schema::table('cheques_pedidos_prepagos', function ($table) {
            $table->dropColumn('valor_pago');
        });
    }
}
