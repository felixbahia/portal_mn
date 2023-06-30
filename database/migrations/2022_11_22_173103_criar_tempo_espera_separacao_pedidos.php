<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CriarTempoEsperaSeparacaoPedidos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tempo_espera_separacao_pedidos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('pedido_id');
            $table->integer('pedido_nasajon');
            $table->uuid('id_pedido_nasajon');
            $table->dateTime('inicio_separacao_manual');
            $table->dateTime('fim_separacao_manual');
            $table->dateTime('fim_separacao_nasajon')->nullable();
            $table->boolean('seperacao_finalizada')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();


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
        Schema::dropIfExists('tempo_espera_separacao_pedidos');
    }
}
