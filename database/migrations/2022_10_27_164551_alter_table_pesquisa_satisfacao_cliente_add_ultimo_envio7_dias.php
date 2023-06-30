<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePesquisaSatisfacaoClienteAddUltimoEnvio7Dias extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pesquisa_satisfacao_clientes', function (Blueprint $table) {
            $table->boolean('envio_7_dias')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pesquisa_satisfacao_clientes', function (Blueprint $table) {
            $table->dropColumn('envio_7_dias');
        });
    }
}
