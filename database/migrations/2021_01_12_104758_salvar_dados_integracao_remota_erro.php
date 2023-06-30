<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SalvarDadosIntegracaoRemotaErro extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('cielo_erros', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cielo_pedido_id');
            $table->text('motivo_erro');
            $table->json('json_enviado');
            $table->json('json_retorno');
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
        Schema::dropIfExists('cielo_erros');
    }
}
