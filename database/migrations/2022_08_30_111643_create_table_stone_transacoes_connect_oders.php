<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableStoneTransacoesConnectOders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stone_pedido_orders', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stone_transacoes_pedido_id');
            $table->string('order_id');
            $table->string('tipo');
            $table->timestamps();
            $table->softDeletes();

            
            $table->foreign('stone_transacoes_pedido_id')
                ->references('id')
                ->on('stone_transacoes_pedidos')
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
        Schema::dropIfExists('stone_pedido_orders');
    }
}
