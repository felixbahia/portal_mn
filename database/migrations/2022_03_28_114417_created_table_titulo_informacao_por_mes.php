<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatedTableTituloInformacaoPorMes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('titulo_informacao_mes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('estabelecimento_codigo', 2);
            $table->integer('mes_emissao');
            $table->integer('ano_emissao');
            $table->integer('diferenca_mes');
            $table->integer('quantidade');
            $table->float('valor');
            $table->float('valor_pre_pago');
            $table->string('representante_codigo');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('titulo_informacao_mes');
    }
}
