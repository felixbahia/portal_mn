<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableLancamentoProjetoPedidoComprasEVendaFutura extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->integer('pedido_compras_nasajon_gerado')->nullable();
            $table->integer('pedido_id')->nullable();
            
            $table->foreign('pedido_id')
                ->references('id')
                ->on('pedido')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lancamento_projetos', function ($table) {
            $table->dropColumn('pedido_compras_nasajon_gerado');
            $table->dropColumn('pedido_id');
        });
    }
}
