<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaRenegociacaoTituloParcelas extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_parcelas', function ($table) {
            $table->float('parcela_sem_encargos')->nullable();
            $table->integer('dias_vencimento')->nullable();
            $table->float('encargo_dia')->nullable();
            $table->float('encargo_periodo')->nullable();
            $table->float('encargo_ragazzi')->nullable();
            $table->float('encargo_total')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulo_titulos', function ($table) {
            $table->dropColumn('parcela_sem_encargos');
            $table->dropColumn('dias_vencimento');
            $table->dropColumn('encargo_dia');
            $table->dropColumn('encargo_periodo');
            $table->dropColumn('encargo_total');
            $table->dropColumn('encargo_ragazzi');
        });
    }
}
