<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TableCreatedAprovacaoRenegociacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('aprovacao_renegociacaos', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('renegociacao_titulos_id');
            $table->boolean('aprovacao_diretoria');
            $table->boolean('aprovacao_automatica');
            $table->boolean('aprovacao_cliente');
            $table->integer('diretoria_users_id')->nullable();
            $table->timestamp('data_aprovacao_diretoria')->nullable();
            $table->timestamp('data_aprovacao_automatica')->nullable();
            $table->timestamp('data_aprovacao_cliente')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->integer('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('renegociacao_titulos_id')
                ->references('id')
                ->on('renegociacao_titulos')
                ->onDelete('NO ACTION');
            $table->foreign('diretoria_users_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('aprovacao_renegociacaos');
    }
}
