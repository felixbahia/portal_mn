<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePedidoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pedido', function(Blueprint $table)
		{
			$table->increments('id');
			$table->date('data_pedido')->nullable();
			$table->integer('usuario')->index('idx_pedido_header_usuario_0');
			$table->string('cod_cliente');
			$table->string('nome_comprador', 50)->nullable();
			$table->string('email_comprador', 50)->nullable();
			$table->integer('status_pedido')->index('idx_pedido_status_pedido');
			$table->integer('estabelecimento');
			$table->integer('pedido_gerado')->nullable();
			$table->boolean('pedido_futuro')->nullable();
			$table->string('condicao_pagamento', 10)->nullable();
			$table->integer('no_pedido_compra')->nullable();
			$table->integer('cod_usuario_autorizador')->nullable()->index('idx_pedido_header_cod_usuario_autorizador_0');
			$table->date('data_previsao_entrega')->nullable();
			$table->string('tipo_frete', 10)->nullable();
			$table->string('observacao', 50)->nullable();
			$table->integer('transportadora')->nullable();
			$table->integer('transportadora_redespacho')->nullable();
			$table->float('comissao', 10, 0)->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->integer('updated_by')->nullable()->index('idx_pedido_updated_by');
			$table->integer('created_by')->nullable()->index('idx_pedido_created_by');
			$table->unique(['id','usuario'], 'unq_pedido_id');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('pedido');
	}

}
