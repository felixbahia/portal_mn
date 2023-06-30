<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableHistoricosPedidosComprasAddPedidoId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('historicos_pedidos_compras', function (Blueprint $table) {
            $table->integer('pedido_id')->nullable();
            $table->integer('lancamento_projetos_id')->change()->nullable(true);

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
        Schema::table('historicos_pedidos_compras', function (Blueprint $table) {
            $table->dropColumn('pedido_id');
            $table->integer('lancamento_projetos_id')->change()->nullable(false);
        });
    }
}
