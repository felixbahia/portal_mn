<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociacaoTitulosAvalistaAcrescimosVeniaConjugal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_avalistas', function (Blueprint $table) {
            $table->string('endereco')->default("");
            $table->string('estado_civil')->default("");
            $table->string('nome_venia_conjugal')->nullable();
            $table->string('cpf_venia_conjugal')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_avalistas', function (Blueprint $table) {
            $table->dropColumn('endereco');
            $table->dropColumn('estado_civil');
            $table->dropColumn('nome_venia_conjugal');
            $table->dropColumn('cpf_venia_conjugal');
        });
    }
}
