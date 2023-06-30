<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaCarrinhoCompraItems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('carrinho_compra_items', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('carrinho_compra_id');
            $table->integer('pedido_item_id');
            $table->string('produto_codigo');
            $table->float('produto_quantidade');
            $table->float('produto_preco');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('carrinho_compra_id')
                ->references('id')
                ->on('carrinho_compras')
                ->onDelete('NO ACTION');
            $table->foreign('pedido_item_id')
                ->references('id')
                ->on('pedido_item')
                ->onDelete('NO ACTION');
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
            $table->foreign('deleted_by')
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
        Schema::dropIfExists('carrinho_de_compras_items');
    }
}
