<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreatePedidoItemTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pedido_item', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('pedido');
			$table->integer('usuario')->index('idx_pedido_item_usuario');
			$table->string('cod_produto');
			$table->integer('quantidade')->nullable();
			$table->float('preco_unitario', 10, 0)->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->integer('updated_by')->nullable()->index('idx_pedido_item_updated_by');
			$table->integer('created_by')->nullable()->index('idx_pedido_item_created_by');
			$table->index(['pedido','usuario'], 'idx_pedido_item_pedido');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('pedido_item');
	}

}
