<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentoProjetoFaccaoData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projeto_faccoes', function ($table) {
            $table->date('data_entrega_cliente')->nullable();
            $table->date('data_previsao_entrega')->nullable();
            $table->string('pedido_compras_gerado_nasajon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projeto_faccoes', function ($table) {
            $table->dropColumn('data_entrega_cliente');
            $table->dropColumn('data_previsao_entrega');
            $table->dropColumn('pedido_compras_gerado_nasajon');
        });
    }
}
