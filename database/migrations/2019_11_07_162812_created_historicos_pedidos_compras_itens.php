<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedHistoricosPedidosComprasItens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historicos_pedidos_compras_itens', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('historicos_pedidos_compras_id');
            $table->integer('necessidades_compras_id');
            $table->integer('lancamento_projeto_produtos_id')->nullable();
            $table->integer('lancamento_projeto_tecidos_id')->nullable();
            $table->string('pedido_compra_item_uuid');
            $table->string('produto_codigo');
            $table->string('produto_unidade');
            $table->float('valor_unitario');
            $table->float('valor_unitario_original');
            $table->float('quantidade');
            $table->float('quantidade_original');
            $table->float('valor_total');
            $table->float('valor_total_original');

            $table->softDeletes();
            $table->timestamps();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->foreign('necessidades_compras_id')
                ->references('id')
                ->on('necessidades_compras')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_produtos_id')
                ->references('id')
                ->on('lancamento_projeto_produtos')
                ->onDelete('NO ACTION');
            $table->foreign('lancamento_projeto_tecidos_id')
                ->references('id')
                ->on('lancamento_projeto_tecidos')
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
        Schema::dropIfExists('historicos_pedidos_compras_itens');
    }
}
