<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaTempoEsperaPedidosAdicionarCampoEmSeparacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tempo_espera_separacao_pedidos', function (Blueprint $table){
            $table->dateTime('em_faturamento_nasajon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tempo_espera_separacao_pedidos', function (Blueprint $table) {
            $table->dropColumn('em_faturamento_nasajon');
        });
    }
}
