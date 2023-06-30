<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidoMaiorEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_maior_estoques', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento');
            $table->string('codigo_produto');
            $table->string('descricao');
            $table->string('unidade')->nullable();
            $table->float('estoque');
            $table->float('compras_aberto');
            $table->float('quantidade');
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
        Schema::dropIfExists('pedido_maior_estoques');
    }
}
