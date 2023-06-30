<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaStonePagamentoRestante extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        Schema::create('stone_transacoes_pagamentos_restantes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id')->nullable();
            $table->integer('stone_transacoes_pedido_id');
            $table->integer('stone_cadastro_maquininha_id');
            $table->integer('stone_pagamento_parciais_id')->nullable();
            $table->string('pre_transaction_token');
            $table->string('pre_transaction_id');
            $table->string('status_pre_transacao')->nullable();
            $table->string('stone_transaction_id')->nullable();
            $table->string('card_brand')->nullable();
            $table->integer('payment_type')->nullable();
            $table->string('status_transacao')->nullable();
            $table->dateTime('data_transacao')->nullable();
            $table->string('transaction_amount')->nullable();
            $table->string('transaction_net_amount')->nullable();
            $table->string('installments_number')->nullable();
            $table->string('card_holder_name')->nullable();
            $table->string('card_number')->nullable();
            $table->string('transaction_authorization_code')->nullable();
            $table->dateTime('prevision_liquidation_date')->nullable();
            $table->boolean('pagamento_parcial')->nullable();
            $table->string('pos_serial_number',50)->nullable();
            $table->string('siclos_transaction_id',100)->nullable();
            $table->dateTime('data_pre_transacao')->nullable();
            $table->boolean('api_nasajon')->nullable();
            $table->uuid('retorno_api_nasajon_pagamento')->nullable();
            $table->uuid('retorno_api_nasajon_cartao')->nullable();
            $table->text('query_api_nasajon')->nullable();
            $table->string('token')->nullable();
            $table->boolean('pago')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stone_pagamento_parciais_id')
                ->references('id')
                ->on('stone_pagamentos_parciais')
                ->onDelete('NO ACTION');
            $table->foreign('stone_transacoes_pedido_id')
                ->references('id')
                ->on('stone_transacoes_pedidos')
                ->onDelete('NO ACTION');
            $table->foreign('stone_cadastro_maquininha_id')
                ->references('id')
                ->on('stone_cadastro_maquininhas')
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

        Schema::table('stone_pagamentos_parciais', function (Blueprint $table){
            $table->integer('stone_transacoes_pedido_id')->nullable()->change();
            $table->integer('stone_transacoes_pagamento_restantes_id')->nullable();

            $table->foreign('stone_transacoes_pagamento_restantes_id')
                ->references('id')
                ->on('stone_transacoes_pagamentos_restantes')
                ->onDelete('NO ACTION');
        });

        Schema::table('stone_transacao_parcelamentos', function (Blueprint $table){
            $table->integer('stone_transacoes_pedido_id')->nullable()->change();
            $table->integer('stone_transacoes_pagamento_restantes_id')->nullable();

            $table->foreign('stone_transacoes_pagamento_restantes_id')
                ->references('id')
                ->on('stone_transacoes_pagamentos_restantes')
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
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table){
            $table->dropColumn('stone_transacoes_pagamento_restantes_id');
        });
        
        Schema::table('stone_transacao_parcelamentos', function (Blueprint $table){
            $table->integer('stone_transacoes_pedido_id')->change();
            $table->dropColumn('stone_transacoes_pagamento_restantes_id');

        });

        Schema::dropIfExists('stone_transacoes_pagamentos_restantes');

    }
}
