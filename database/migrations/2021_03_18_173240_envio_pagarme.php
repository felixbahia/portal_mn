<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EnvioPagarme extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
    public function up()
    {
		Schema::create('pagarme_log_envios', function (Blueprint $table) {
			$table->increments('id');
            $table->integer('cielo_pedido_id');
			$table->string('codigo');
			$table->text('envio');
			$table->timestamp('datahora_envio');
            $table->foreign('cielo_pedido_id')
                ->references('id')
                ->on('cielo_pedidos')
                ->onDelete('NO ACTION');
		});

		Schema::table('cielo_pedidos', function (Blueprint $table) {
			$table->timestamp('datahora_envio_email')->nullable();
			$table->timestamp('datahora_envio_pagarme')->nullable();
			$table->timestamp('datahora_retorno_pagarme')->nullable();
		});
    }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
    public function down()
    {
        Schema::dropIfExists('pagarme_log_envios');

		Schema::table('cielo_pedidos', function (Blueprint $table) {
			$table->dropColumn('datahora_envio_email');
			$table->dropColumn('datahora_envio_pagarme');
			$table->dropColumn('datahora_retorno_pagarme');
		});
	}
}
