<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaAuditoriaComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('auditoria_comissaos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_item_id');
            $table->integer('pedido_id');
            $table->integer('campanha_id');
            $table->float('comissao_anterior');
            $table->float('comissao_nova');
            $table->timestamps();

            $table->foreign('pedido_item_id')
            ->references('id')
            ->on('pedido_item')
            ->onDelete('NO ACTION');
            $table->foreign('pedido_id')
            ->references('id')
            ->on('pedido')
            ->onDelete('NO ACTION');
            $table->foreign('campanha_id')
            ->references('id')
            ->on('campanhas')
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
        Schema::dropIfExists('auditoria_comissaos');
    }
}
