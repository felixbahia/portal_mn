<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EntradasRelacaoNotas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_entrada_relacao_notas', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('notas_importadas_entrada_id');
            $table->integer('id_nfe');
            $table->foreign('notas_importadas_entrada_id')
                ->references('id')
                ->on('notas_importadas_entradas');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notas_importadas_entrada_relacao_cte_nfes');
    }
}
