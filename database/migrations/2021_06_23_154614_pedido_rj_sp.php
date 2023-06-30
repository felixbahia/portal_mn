<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidoRjSp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_rj_sp', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id');

            $table->integer('pedido_transferencia_id')->nullable();
            $table->uuid('pedido_nasajon_transferencia_id')->nullable();

            $table->integer('pedido_venda_id')->nullable();
            $table->uuid('pedido_nasajon_venda_id')->nullable();

            $table->boolean('liberado')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
                ->onDelete('NO ACTION');

			$table->foreign('pedido_transferencia_id')
				->references('id')
				->on('pedido')
				->onDelete('NO ACTION');

			$table->foreign('pedido_venda_id')
				->references('id')
				->on('pedido')
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
        Schema::dropIfExists('pedido_rj_sp');
    }
}
