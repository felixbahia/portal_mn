<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableRenegociacaoAvalista extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renegociacao_titulo_avalistas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('renegociacao_titulos_id');
            $table->string('nome');
            $table->string('cpf');
            $table->string('email');
            $table->string('ip')->nullable();
            $table->date('data_assinatura')->nullable();
            $table->boolean('aceito')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('renegociacao_titulos_id')
                ->references('id')
                ->on('renegociacao_titulos')
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
        Schema::dropIfExists('renegociacao_titulo_avalistas');
    }
}
