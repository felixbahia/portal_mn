<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CancelamentoPedidoNasajonLogsAlterColumnUsuario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cancelamento_pedido_nasajon_logs', function ($table) {
            $table->renameColumn('usuario', 'user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cancelamento_pedido_nasajon_logs', function ($table) {
            $table->renameColumn('user_id', 'usuario');
        });
    }
}
