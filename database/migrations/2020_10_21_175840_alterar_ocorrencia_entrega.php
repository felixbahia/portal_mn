<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarOcorrenciaEntrega extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ocorrencias_de_entregas', function (Blueprint $table) {
            $table->renameColumn('cnpj_transportadora','transportadora_cnpj');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ocorrencias_de_entregas', function (Blueprint $table) {
            $table->renameColumn('transportadora_cnpj', 'cnpj_transportadora');
        });
    }
}
