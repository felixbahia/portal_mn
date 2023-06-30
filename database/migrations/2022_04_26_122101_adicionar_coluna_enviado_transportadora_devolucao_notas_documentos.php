<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarColunaEnviadoTransportadoraDevolucaoNotasDocumentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_notas_documentos', function (Blueprint $table) {
            $table->boolean('enviado_transportadora')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devolucao_notas_documentos', function (Blueprint $table) {
            $table->dropColumn('enviado_transportadora');
        });
    }
}
