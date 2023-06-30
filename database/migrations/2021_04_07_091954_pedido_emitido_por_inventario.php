<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidoEmitidoPorInventario extends Migration{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up(){
		Schema::create('pedido_inventarios', function (Blueprint $table) {
			$table->increments('id');
			$table->integer('pedido_id');
			$table->integer('inventario_historico_id');
			$table->integer('created_by');
			$table->timestamps();

			$table->foreign('created_by')
				->references('id')
				->on('users')
				->onDelete('NO ACTION');
			$table->foreign('pedido_id')
				->references('id')
				->on('pedido')
				->onDelete('NO ACTION');
			$table->foreign('inventario_historico_id')
				->references('id')
				->on('inventario_historicos')
				->onDelete('NO ACTION');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down(){
		Schema::dropIfExists('pedido_inventarios');
	}
}
