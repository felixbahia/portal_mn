<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaRenegociacaoTituloTitulos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulo_titulos', function ($table) {
            $table->integer('dias_vencido')->nullable();
            $table->float('encargo_dia')->nullable();
            $table->float('encargo_periodo')->nullable();
            $table->float('encargo_total')->nullable();
            $table->float('valor_total')->nullable();
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
            $table->dropColumn('dias_vencido');
            $table->dropColumn('encargo_dia');
            $table->dropColumn('encargo_periodo');
            $table->dropColumn('encargo_total');
            $table->dropColumn('valor_total');
        });
    }
}
