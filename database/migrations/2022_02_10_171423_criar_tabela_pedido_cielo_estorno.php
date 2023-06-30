<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaPedidoCieloEstorno extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cielo_pedidos_estornos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cielo_pedido_transacao_id');
            $table->integer('cielo_pedido_id');
            $table->string('token_estorno')->nullable();
            $table->float('valor_estorno')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cielo_pedido_transacao_id')
                ->references('id')
                ->on('cielo_pedido_transacaos')
                ->onDelete('NO ACTION');
            $table->foreign('cielo_pedido_id')
                ->references('id')
                ->on('cielo_pedidos')
                ->onDelete('NO ACTION');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('cielo_pedidos_estornos');
    }
}
