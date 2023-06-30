<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCancelamentoPedidoNasajonLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cancelamento_pedido_nasajon_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('pedido_nasajon_id');
            $table->string('pedido_nasajon_numero');
            $table->date('pedido_nasajon_emissao');
            $table->string('cliente');
            $table->integer('usuario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cancelamento_pedido_nasajon_logs');
    }
}
