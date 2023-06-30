<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateProdutosSemEstoquesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('produtos_sem_estoques', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido');
            $table->string('cod_produto');
            $table->integer('user');
            $table->decimal('qtd');
            $table->string('cliente');
            $table->date('data_pedido')->nullable();
            $table->boolean('pedido_futuro');
            $table->date('data_entrega')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('produtos_sem_estoques');
    }
}
