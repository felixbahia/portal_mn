<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableChequesPedidosPrepagosUniqueKey extends Migration
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
            $table->index(['cheque_id', 'pedido_prepago_id']);
            $table->unique(['cheque_id', 'pedido_prepago_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $table->$table->dropIndex();
        $table->dropUnique('cheques_pedidos_prepagos_cheque_id_pedido_prepago_id_unique');
    }
}
