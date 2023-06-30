<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTableStoneTransacoesConnectAvulsas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('stone_retorno_transacoes_avulsas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id')->nullable();
            $table->integer('stone_transacoes_pedido_id')->nullable();
            $table->string('id_web_hook');
            $table->string('account_id');
            $table->string('account_name');
            $table->string('type');
            $table->string('data_id');
            $table->string('data_code');
            $table->integer('data_amount');
            $table->string('data_status',50);
            $table->dateTime('data_created_at')->nullable();
            $table->string('order_id');
            $table->string('order_code');
            $table->string('order_currency');
            $table->string('order_status');
            $table->string('customer_id');
            $table->string('customer_name');
            $table->boolean('customer_delinquent')->nullable();
            $table->dateTime('customer_created_at')->nullable();
            $table->string('metadata_scheme_name');
            $table->string('metadata_account_funding_source')->nullable();
            $table->string('metadata_autorization_code')->nullable();
            $table->string('metadata_account_holder_name')->nullable();
            $table->string('metadata_initiator_transaction_key')->nullable();
            $table->integer('metadata_installment_quantity')->nullable();
            $table->string('metadata_installment_type')->nullable();
            $table->string('metadata_terminal_serial_number',50)->nullable();
            $table->dateTime('metadata_transaction_time')->nullable();
            $table->text('json')->nullable();
            $table->text('retorno_api_nasajon')->nullable();
            $table->boolean('api_nasajon')->nullable();
            $table->boolean('integracao_pedido')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('stone_transacoes_pedido_id')
                ->references('id')
                ->on('stone_transacoes_pedidos')
                ->onDelete('NO ACTION');
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
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
        Schema::dropIfExists('stone_retorno_transacoes_avulsas');
    }
}
