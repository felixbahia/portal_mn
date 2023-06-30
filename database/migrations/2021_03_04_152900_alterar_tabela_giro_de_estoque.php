<?php

use App\GiroDeEstoque;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaGiroDeEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        GiroDeEstoque::truncate();
        Schema::table('giro_de_estoques', function (Blueprint $table) {
            $table->float('compras_mes_atual');
            $table->float('compras_proximo_mes');
            $table->float('compras_mes_seguinte');
            $table->float('compras_proximos_meses');
            $table->dropColumn('previsao_chegada');
            $table->dropColumn('compras_aberto');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('giro_de_estoques', function (Blueprint $table) {
            $table->dropColumn('compras_mes_atual');
            $table->dropColumn('compras_proximo_mes');
            $table->dropColumn('compras_mes_seguinte');
            $table->dropColumn('compras_proximos_meses');
            $table->date('previsao_chegada');
            $table->float('compras_aberto');
        });
    }
}
