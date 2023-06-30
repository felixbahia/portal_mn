<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCampoTabelaDevolucaoNotaMotivos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_nota_motivos', function (Blueprint $table) {
            $table->string('afeta_premiacao', 3)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devolucao_nota_motivos', function (Blueprint $table) {
            $table->dropColumn('afeta_premiacao');
        });
    }
}
