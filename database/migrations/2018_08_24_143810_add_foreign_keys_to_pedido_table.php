<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToPedidoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('pedido', function(Blueprint $table)
		{
			$table->foreign('usuario', 'fk_pedido_users')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('updated_by', 'fk_pedido_updated_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('created_by', 'fk_pedido_created_by')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('status_pedido', 'fk_pedido_status_pedido')->references('id')->on('status_pedido')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('cod_usuario_autorizador', 'fk_pedido_aprovador')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('pedido', function(Blueprint $table)
		{
			$table->dropForeign('fk_pedido_users');
			$table->dropForeign('fk_pedido_updated_by');
			$table->dropForeign('fk_pedido_created_by');
			$table->dropForeign('fk_pedido_status_pedido');
			$table->dropForeign('fk_pedido_aprovador');
		});
	}

}
