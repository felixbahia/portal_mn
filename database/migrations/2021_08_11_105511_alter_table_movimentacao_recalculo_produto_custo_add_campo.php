<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMovimentacaoRecalculoProdutoCustoAddCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
            Schema::table('movimentacao_recalculo', function (Blueprint $table) {
                $table->float('custo_armazem_saldo')->nullable();

            });

            Schema::table('produtos_custos', function (Blueprint $table) {
                $table->float('custo_medio_armazem')->nullable();
            });


  }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->dropColumn('custo_armazem_saldo');

        });
        
        Schema::table('produtos_custos', function (Blueprint $table) {
            $table->dropColumn('custo_medio_armazem');
        });
    }

}