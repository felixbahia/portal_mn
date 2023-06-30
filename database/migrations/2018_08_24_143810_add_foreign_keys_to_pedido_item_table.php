<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPedidoItemTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('pedido_item', function(Blueprint $table)
		{
			$table->foreign('usuario', 'fk_pedido_item_users')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('created_by', 'fk_pedido_item_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('updated_by', 'fk_pedido_item_updated_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('pedido', 'fk_pedido_item_pedido')->references('id')->on('pedido')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('pedido_item', function(Blueprint $table)
		{
			$table->dropForeign('fk_pedido_item_users');
			$table->dropForeign('fk_pedido_item_created_by');
			$table->dropForeign('fk_pedido_item_updated_by');
			$table->dropForeign('fk_pedido_item_pedido');
		});
	}

}
