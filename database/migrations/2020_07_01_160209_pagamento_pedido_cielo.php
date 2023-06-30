<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PagamentoPedidoCielo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cielo_pedidos', function ($table) {
            $table->boolean('pago')->default(false);
            $table->dateTime('data_pagamento')->nullable();
            $table->boolean('liberado')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cielo_pedidos', function ($table) {
            $table->dropColumn('pago');
            $table->dropColumn('data_pagamento');
            $table->dropColumn('liberado');
        });
    }
}
