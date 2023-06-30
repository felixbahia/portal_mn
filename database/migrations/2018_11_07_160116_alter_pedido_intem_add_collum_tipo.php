<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterPedidoIntemAddCollumTipo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_item', function ($table) {
            $table->string('coluna', 2)->nullable(true);
            $table->double('comissao', 5, 2)->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_item', function ($table) {
            $table->dropColumn('coluna');
            $table->dropColumn('comissao');
        });
    }
}
