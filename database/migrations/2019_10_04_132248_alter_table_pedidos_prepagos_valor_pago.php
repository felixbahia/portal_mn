<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidosPrepagosValorPago extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedidos_prepagos', function ($table) {
            $table->decimal('valor_pago')->default(0);
            $table->decimal('valor_restante')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedidos_prepagos', function ($table) {
            $table->dropColumn('valor_pago');
            $table->dropColumn('valor_restante');
        });
    }
}
