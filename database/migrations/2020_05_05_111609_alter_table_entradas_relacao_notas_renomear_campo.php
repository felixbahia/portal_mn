<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEntradasRelacaoNotasRenomearCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_importadas_entrada_relacao_notas', function (Blueprint $table) {
            $table->renameColumn('id_nfe', 'notas_importadas_entradas_id_nfe');
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
            $table->dropColumn('notas_importadas_entradas_id_nfe');
        });
    }
}
