<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableRenegociacaoTituloParcelasRetornoNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_parcelas', function (Blueprint $table) {
            $table->string('retorno_nasajon')->nullable();
            $table->uuid('titulo_id_nasajon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_parcelas', function (Blueprint $table) {
            $table->dropColumn('retorno_nasajon');
            $table->dropColumn('titulo_id_nasajon');
        });
    }
}
