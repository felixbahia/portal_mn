<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteredTableFaturamentoOnlineAddFormapagamento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('faturamento_online_condicao_de_pagamentos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('faturamento_online_id');
            $table->string('formapagamento_codigo')->nullable();
            $table->string('formapagamento_descricao')->nullable();
            $table->float('valor')->nullable();
            $table->integer('created_by');
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
    
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('faturamento_online_id')
                ->references('id')
                ->on('faturamento_online')
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
        Schema::dropIfExists('faturamento_online_condicao_de_pagamentos');
    }
}
