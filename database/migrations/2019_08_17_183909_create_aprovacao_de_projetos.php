<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAprovacaoDeProjetos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aprovacao_de_projetos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('projeto_id');
            $table->integer('aprovador_id')->nullable();
            $table->integer('status')->nullable();            
            $table->tinyInteger('nivel_aprovacao');
            $table->boolean('credito')->nullable(true);
            $table->boolean('condicao_pagamento')->nullable(true);
            $table->boolean('preco')->nullable(true);
			$table->boolean('integracao')->default(false);
			$table->integer('aprovacao_credito_user_id')->nullable();
            $table->integer('aprovacao_preco_user_id')->nullable();
			$table->boolean('credito_sem_limite')->nullable(true);
            $table->boolean('credito_data_limite')->nullable(true);
            $table->boolean('preco_limite')->nullable(true);
            $table->boolean('preco_desconto')->nullable(true);
            $table->tinyInteger('nivel_aprovacao_credito')->nullable(true);
            $table->timestamp('data_aprovacao_credito')->nullable(true);
            $table->tinyInteger('nivel_aprovacao_preco')->nullable(true);
            $table->timestamp('data_aprovacao_preco')->nullable(true);
			$table->integer('created_by')->nullable();
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
        Schema::dropIfExists('aprovacao_de_projetos');
    }
}
