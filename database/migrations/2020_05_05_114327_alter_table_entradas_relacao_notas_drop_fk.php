<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEntradasRelacaoNotasDropFk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_importadas_entrada_relacao_notas', function (Blueprint $table) {
            $table->dropForeign(['notas_importadas_entrada_id']);
            $table->integer('notas_importadas_entradas_id_cte');
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
        Schema::table('notas_importadas_entrada_relacao_notas', function (Blueprint $table) {
            $table->dropForeign(['notas_importadas_entradas_id_cte']);
        });
    }
}
