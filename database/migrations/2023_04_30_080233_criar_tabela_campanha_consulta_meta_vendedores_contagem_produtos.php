<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaCampanhaConsultaMetaVendedoresContagemProdutos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campanhas_consulta_meta_vendedores_contagem_produtos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('campanhas_consulta_meta_vendedores_produto_id');
            $table->string('produto_codigo');
            $table->float('metros');
            $table->float('unidade');
            $table->float('kilo');
            $table->float('valor');
            $table->float('devolucao_metragem');
            $table->float('devolucao_valor');
            $table->integer('pedido_id');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('campanhas_consulta_meta_vendedores_produto_id')
                ->references('id')
                ->on('campanhas_consulta_meta_vendedores_produtos')
                ->onDelete('NO ACTION');
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
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
        Schema::dropIfExists('campanhas_consulta_meta_vendedores_contagem_produtos');
    }
}
