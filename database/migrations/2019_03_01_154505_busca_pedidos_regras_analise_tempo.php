<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BuscaPedidosRegrasAnaliseTempo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedidos_monitorados', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('estabelecimento');
            $table->integer('pedido');
            $table->integer('status_pedido_monitorado');
            $table->dateTime('emissao_data_hora');
            $table->integer('emissao_user');
            $table->dateTime('aprovacao_data_hora')->nullable();
            $table->integer('aprovacao_user')->nullable();
            $table->dateTime('faturamento_data_hora')->nullable();
            $table->integer('faturamento_user')->nullable();
            $table->integer('tempo_separacao')->nullable();
            $table->integer('quantidade_alertas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('emissao_user')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('aprovacao_user')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('faturamento_user')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('pedidos_monitorados');
    }
}
