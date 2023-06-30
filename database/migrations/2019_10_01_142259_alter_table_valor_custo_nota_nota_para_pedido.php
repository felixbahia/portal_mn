<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableValorCustoNotaNotaParaPedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('valor_custo_notas', function ($table) {
            $table->dropColumn('nota_numero');
            $table->string('numero_pedido');

            $table->dropColumn('emissao');
            $table->date('data_compra');
        });

        Schema::table('valor_custo_nota_produtos', function ($table) {
            $table->dropColumn('nota_numero');
            $table->string('numero_pedido');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('valor_custo_notas', function ($table) {
            $table->dropColumn('numero_pedido');
            $table->string('nota_numero');

            $table->date('emissao');
            $table->dropColumn('data_compra');
        });

        Schema::table('valor_custo_nota_produtos', function ($table) {
            $table->dropColumn('numero_pedido');
            $table->string('nota_numero');
        });
    }
}
