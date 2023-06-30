<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableBaixarTitulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('baixa_titulos', function (Blueprint $table) {
            $table->increments('id');
            $table->uuid('titulo_nasajon_id');
            $table->string('titulo_numero');
            $table->date('data_baixa');
            $table->float('valor_recebido');
            $table->float('desconto_valor');
            $table->float('taxa_boleto_banco_valor');
            $table->float('honorarios_cliente_valor');
            $table->float('honorarios_mn_valor');
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
        Schema::dropIfExists('pedido_usar_creditos');
    }
}
