<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidoAdicionarCamposDeContaEOrdem extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido', function ($table) {
            $table->boolean('conta_e_ordem')->nullable();
            $table->string('cod_cliente_conta_e_ordem')->nullable();
            $table->string('tipo_venda')->nullable();
            $table->string('tipo_frete_redespacho')->nullable();
            $table->decimal('valor_frete_redespacho')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido', function ($table) {
            $table->dropColumn('conta_e_ordem');
            $table->dropColumn('cod_cliente_conta_e_ordem');
            $table->dropColumn('tipo_venda');
            $table->dropColumn('valor_frete_redespacho');
            $table->dropColumn('tipo_frete_redespacho');
        });
    }
}
