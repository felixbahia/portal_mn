<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTabelaTransacaoStone extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id')->nullable();
            $table->integer('stone_cadastro_maquininha_id');
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
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

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
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stone_transacoes_pedidos');
    }
}
