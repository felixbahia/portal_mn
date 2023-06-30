<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentoProjetoStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->string('nome_projeto', 60)->nullable()->change();
            $table->float('preco_venda')->nullable()->change();
            $table->string('pedido')->nullable()->change();
            $table->integer('condicoes_pagamento_web_id')->nullable()->change();
            $table->float('valor_total_pedido')->nullable()->change();
            $table->float('quantidade_total')->nullable()->change();
            $table->float('comissao')->nullable()->change();
            $table->float('custo_total_margem')->nullable()->change();
            $table->float('mark_up_real')->nullable()->change();
            $table->float('acima_tabela')->nullable()->change();
            $table->float('custo_tecido')->nullable()->change();
            $table->float('custo_insumo')->nullable()->change();
            $table->float('mao_obra')->nullable()->change();
            $table->float('custo_total')->nullable()->change();
            $table->float('custo_unitario_mn')->nullable()->change();
            $table->float('margem')->nullable()->change();
            $table->integer('status')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->dropColumn('status');
        });
    }
}
