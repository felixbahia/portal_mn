<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CamposParaPedidoObservacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido', function (Blueprint $table) {
            $table->boolean('enfestar')->nullable()->default(false);
            $table->boolean('bater_amostra')->nullable()->default(false);
            $table->boolean('incluir_cartelas')->nullable()->default(false);
            $table->boolean('transportadora_retira')->nullable()->default(false);
            $table->time('transportadora_retira_horario')->nullable();
            $table->boolean('transportadora_retira_imediato')->nullable()->default(false);
            $table->boolean('metragem_exata')->nullable()->default(false);
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido', function (Blueprint $table) {
            $table->dropColumn('enfestar');
            $table->dropColumn('bater_amostra');
            $table->dropColumn('incluir_cartelas');
            $table->dropColumn('transportadora_retira');
            $table->dropColumn('transportadora_retira_horario');
            $table->dropColumn('transportadora_retira_imediato');
            $table->dropColumn('metragem_exata');
        });
    }
}
