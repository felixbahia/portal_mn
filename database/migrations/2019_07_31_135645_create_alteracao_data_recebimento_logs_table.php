<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlteracaoDataRecebimentoLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alteracao_data_recebimento_logs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento');
            $table->string('numero_pedido');
            $table->date('data_anterior');
            $table->date('data_atual');
            $table->integer('usuario');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alteracao_data_recebimento_logs');
    }
}
