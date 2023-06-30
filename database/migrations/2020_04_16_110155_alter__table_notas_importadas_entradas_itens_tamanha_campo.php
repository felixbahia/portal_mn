<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasImportadasEntradasItensTamanhaCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_importadas_entradas_itens', function (Blueprint $table) {
            $table->string('comercial_unidade', 10)->change();
            $table->string('tributavel_unidade', 10)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_importadas_entradas_itens', function (Blueprint $table) {
            $table->drop('comercial_unidade');
            $table->drop('tributavel_unidade');
        });
    }
}
