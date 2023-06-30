<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidosPagamentosParciais extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('stone_transacoes_pedido_id');
            $table->integer('stone_cadastro_maquininha_id');
            $table->uuid('forma_pagamento');
            $table->uuid('parcelamento')->nullable();
            $table->float('valor');
            $table->string('codigo_autoriazacao',30);
            $table->date('data_autorizacao');
            $table->string('documento_cartao');
            $table->uuid('contrato_cartao');
            $table->string('cnpj_operadora');
            $table->uuid('meio_eletronico');
            $table->uuid('operadora');
            $table->uuid('bandeira');
            $table->string('serial');
            $table->uuid('retorno_api_atualizar_cartao_nasajon');
            $table->integer('tipo_operacao');
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stone_cadastro_maquininha_id')
                ->references('id')
                ->on('stone_cadastro_maquininhas')
                ->onDelete('NO ACTION');
            $table->foreign('stone_transacoes_pedido_id')
                ->references('id')
                ->on('stone_transacoes_pedidos')
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
        Schema::dropIfExists('stone_pagamentos_parciais');
    }
}
