<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociacaoTituloTitulosValor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_titulos', function (Blueprint $table) {
            $table->float('titulo_valor');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_titulos', function (Blueprint $table) {
            $table->dropColumn('titulo_valor');
        });
    }
}
