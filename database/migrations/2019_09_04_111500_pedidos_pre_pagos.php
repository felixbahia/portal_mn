<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidosPrePagos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedidos_prepagos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id');
            $table->uuid('pedido_nasajon_id');
            $table->string('pedido_nasajon_numero');
            $table->string('titulo_nasajon')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['pedido_id']);
            $table->index(['pedido_nasajon_id']);
            
            
            $table->foreign('pedido_id')->references('id')->on('pedido')->onUpdate('NO ACTION')->onDelete('NO ACTION');

            $table->foreign('created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('updated_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('deleted_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pedidos_prepagos');
    }
}
