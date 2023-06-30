<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMovimentacaoAddImportacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
             $table->float('item_precototal')->nullable();
             $table->float('item_ratdespaduaneira')->nullable();
             $table->float('custo_importacao_outrasdespesas')->nullable();
             $table->float('custo_importacao_ii')->nullable();
             $table->float('custo_importacao_aframm')->nullable();
             $table->float('custo_importacao_siscomex')->nullable();
             $table->float('custo_importacao_pis')->nullable();
             $table->float('custo_importacao_cofins')->nullable();
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
            $table->dropColumn('item_precototal');
            $table->dropColumn('item_ratdespaduaneira');
            $table->dropColumn('custo_importacao_outrasdespesas');
            $table->dropColumn('custo_importacao_ii');
            $table->dropColumn('custo_importacao_aframm');
            $table->dropColumn('custo_importacao_siscomex');
            $table->dropColumn('custo_importacao_pis');
            $table->dropColumn('custo_importacao_cofins');
       });
    }
}
