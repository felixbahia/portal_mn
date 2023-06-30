<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCamposTabelaRenegociacaoTituloParcelas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_parcelas', function ($table) {
            $table->float('parcela_sem_honorario')->nullable();
            $table->float('encargo_dia_ragazzi')->nullable();
            $table->float('encargo_periodo_ragazzi')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_parcelas', function ($table) {
            $table->dropColumn('parcela_sem_honorario');
            $table->dropColumn('encargo_dia_ragazzi');
            $table->dropColumn('encargo_periodo_ragazzi');
        });
    }
}
