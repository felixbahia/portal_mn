<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableHistoricoPedidoCompra extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('historicos_pedidos_compras', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('lancamento_projetos_id')->nullable();
            $table->string('pedido_compra_uuid');
            $table->string('pedido_compra_numero')->nullable();
            $table->string('tipo');
            $table->string('estabelecimento_codigo',2);
            $table->string('fornecedor_cnpj_cpf');
            $table->string('condicoes_pagamento_web_id');
            $table->string('forma_pagamento_uuid');
            $table->string('parcelamento_uuid');
            $table->string('indicador_pagamento');
            $table->string('cfop');
            $table->string('tipo_operacao');
            $table->string('modo_compra');
            $table->date('data_entrega');
            $table->float('valor_total');

            $table->softDeletes();
            $table->timestamps();

            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();

            $table->foreign('lancamento_projetos_id')
                ->references('id')
                ->on('lancamento_projetos')
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
        Schema::dropIfExists('historicos_pedidos_compras');
    }
}
