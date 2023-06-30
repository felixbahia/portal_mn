<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedCreditoEmAbertoLiberacaoPedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pedido_usar_credito_historico_liberacoes', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_usar_creditos_id');
            $table->string('codigo_estabelecimento', 2);
            $table->string('numero');
            $table->integer('parcela');
            $table->date('vencimento');
            $table->float('valor');
            $table->string('conta_agencia')->nullable();
            $table->string('conta_agencia_digito')->nullable();
            $table->string('conta_numero')->nullable();
            $table->string('conta_digito')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('pedido_usar_creditos_id')
                ->references('id')
                ->on('pedido_usar_creditos')
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
        Schema::dropIfExists('pedido_usar_credito_historico_liberacoes');
    }
}
