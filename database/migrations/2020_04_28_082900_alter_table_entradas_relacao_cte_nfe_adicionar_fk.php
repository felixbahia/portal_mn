<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEntradasRelacaoCteNfeAdicionarFk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notas_importadas_entrada_relacao_cte_nves', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('notas_importadas_entradas_id_cte');
            $table->integer('notas_importadas_entradas_id_nfe');
            $table->foreign('notas_importadas_entradas_id_cte')
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
        Schema::drop('notas_importadas_entrada_relacao_cte_nfes');
    }
}
