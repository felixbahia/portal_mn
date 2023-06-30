<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteredTableLogCancelamentoPedidosAddMotivoCancelamento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('log_cancelamento_pedidos', function (Blueprint $table) {
            $table->integer('motivo_cancelamento_pedidos_id')->nullable();

            $table->foreign('motivo_cancelamento_pedidos_id')
                ->references('id')
                ->on('motivo_cancelamento_pedidos')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('log_cancelamento_pedidos', function (Blueprint $table) {
            $table->dropColumn('motivo_cancelamento_pedidos_id');
        });
    }
}
