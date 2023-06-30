<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidoCielo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cielo_status', function (Blueprint $table) {
            $table->increments('id');
            $table->string('descricao');
            $table->string('token');
            $table->boolean('lio')->default(false);
            $table->boolean('ecommerce')->default(false);
            $table->timestamps();
        });

        Schema::create('cielo_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id');
            $table->uuid('pedido_nasajon_id')->nullable();
            $table->integer('cielo_status_id');
            $table->uuid('pedido_uuid');
            $table->string('referencia');
            $table->float('valor_total')->default(0)->nullable();
            $table->float('valor_pago')->default(0)->nullable();
            $table->boolean('lio')->default(false);
            $table->boolean('ecommerce')->default(false);
            $table->text('json_criacao')->nullable();
            $table->text('json_retorno')->nullable();
            $table->timestamps();

            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
                ->onDelete('NO ACTION');

            $table->foreign('cielo_status_id')
                ->references('id')
                ->on('cielo_status')
                ->onDelete('NO ACTION');

        });

        Schema::create('cielo_pedido_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cielo_pedido_id');
            $table->string('produto_codigo');
            $table->uuid('produto_uuid');
            $table->float('valor_unitario');
            $table->float('quantidade');
            $table->string('unidade');
            $table->text('json_criacao');
            $table->timestamps();

            $table->foreign('cielo_pedido_id')
                ->references('id')
                ->on('cielo_pedidos')
                ->onDelete('NO ACTION');
        });

        Schema::create('cielo_pedido_transacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cielo_pedido_id');
            $table->uuid('id_uuid');
            $table->string('terminal_numero');
            $table->string('codigo_autorizacao');
            $table->string('numero');
            $table->float('valor');
            $table->string('tipo_pagamento');
            $table->text('json_retorno');
            $table->timestamps();

            $table->foreign('cielo_pedido_id')
                ->references('id')
                ->on('cielo_pedidos')
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
        Schema::dropIfExists('cielo_pedido_transacaos');
        Schema::dropIfExists('cielo_pedido_items');
        Schema::dropIfExists('cielo_pedidos');
        Schema::dropIfExists('cielo_status');
    }
}
