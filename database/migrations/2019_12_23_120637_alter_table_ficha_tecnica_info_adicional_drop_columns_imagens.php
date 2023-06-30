<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableFichaTecnicaInfoAdicionalDropColumnsImagens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ficha_tecnica_info_adicionals', function ($table) {
            $table->dropColumn('etiqueta_tamanho');
            $table->dropColumn('etiqueta_composicao');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ficha_tecnica_info_adicionals', function ($table) {
            $table->string('etiqueta_tamanho');
            $table->string('etiqueta_composicao');
        });
    }
}
