<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ItemPedidoProducao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_item', function (Blueprint $table) {
            $table->string('codigo_tecidos_base')->nullable();
            $table->string('codigo_desenho')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_item', function (Blueprint $table) {
            $table->dropColumn('codigo_tecidos_base');
            $table->dropColumn('codigo_desenho');
        });
    }
}
