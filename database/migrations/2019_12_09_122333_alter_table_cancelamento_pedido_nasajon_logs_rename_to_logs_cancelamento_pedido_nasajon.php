<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCancelamentoPedidoNasajonLogsRenameToLogsCancelamentoPedidoNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('cancelamento_pedido_nasajon_logs', 'log_cancelamento_pedidos');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::rename('log_cancelamento_pedidos', 'cancelamento_pedido_nasajon_logs');
    }
}
