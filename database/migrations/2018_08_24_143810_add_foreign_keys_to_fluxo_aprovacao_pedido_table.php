<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddForeignKeysToFluxoAprovacaoPedidoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('fluxo_aprovacao_pedido', function(Blueprint $table)
		{
			$table->foreign('usuario', 'fk_fluxo_aprovacao_users')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('updated_by', 'fk_fluxo_aprovacao_pedido_users')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('status_pedido', 'fk_fluxo_aprovacao_pedido_status_pedido')->references('id')->on('status_pedido')->onUpdate('NO ACTION')->onDelete('NO ACTION');
			$table->foreign('pedido', 'fk_fluxo_aprovacao_pedido')->references('id')->on('pedido')->onUpdate('NO ACTION')->onDelete('NO ACTION');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('fluxo_aprovacao_pedido', function(Blueprint $table)
		{
			$table->dropForeign('fk_fluxo_aprovacao_users');
			$table->dropForeign('fk_fluxo_aprovacao_pedido_users');
			$table->dropForeign('fk_fluxo_aprovacao_pedido_status_pedido');
			$table->dropForeign('fk_fluxo_aprovacao_pedido');
		});
	}

}
