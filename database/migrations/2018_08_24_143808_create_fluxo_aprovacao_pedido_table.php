<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateFluxoAprovacaoPedidoTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('fluxo_aprovacao_pedido', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('pedido')->nullable()->index('idx_fluxo_aprovacao_pedido');
			$table->dateTime('data_modificacao')->nullable();
			$table->integer('status_pedido')->nullable()->index('idx_fluxo_aprovacao_pedido_status_pedido');
			$table->integer('usuario')->index('idx_fluxo_aprovacao_usuario');
			$table->string('observacao')->nullable();
			$table->softDeletes();
			$table->timestamps();
			$table->integer('updated_by')->nullable()->index('idx_fluxo_aprovacao_pedido_updated_by');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('fluxo_aprovacao_pedido');
	}

}
