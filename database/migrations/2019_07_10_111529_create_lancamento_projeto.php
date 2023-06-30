<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateLancamentoProjeto extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lancamento_projetos', function (Blueprint $table) {
            $table->increments('id');
            $table->string('nome_projeto', 60);
            $table->float('preco_venda');
            $table->string('pedido');
            $table->integer('condicoes_pagamento_web_id');
            $table->float('valor_total_pedido');
            $table->float('quantidade_total');
            $table->float('comissao');
            $table->float('custo_total_margem');
            $table->float('mark_up_real');
            $table->float('acima_tabela');
            $table->float('custo_tecido');
            $table->float('custo_insumo');
            $table->float('mao_obra');
            $table->float('custo_total');
            $table->float('custo_unitario_mn');
            $table->float('margem');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

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
        Schema::dropIfExists('lancamento_projetos');
    }
}
