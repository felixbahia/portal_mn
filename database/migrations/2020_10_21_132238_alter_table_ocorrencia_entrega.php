<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableOcorrenciaEntrega extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ocorrencias_de_entregas', function (Blueprint $table) {
            $table->string('cnpj_transportadora')->nullable();
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
            $table->dropColumn('cnpj_transportadora');
        });
    }
}
