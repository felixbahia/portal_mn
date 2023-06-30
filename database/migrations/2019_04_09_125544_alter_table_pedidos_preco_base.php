<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTablePedidosPrecoBase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido', function ($table) {
            $table->string('frete_preco')->nullable();
        });

        Schema::table('pedido_item', function ($table) {
            $table->float('preco_base')->nullable();
            $table->float('coluna_a')->nullable();
            $table->float('coluna_b')->nullable();
            $table->float('coluna_c')->nullable();
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
            $table->dropColumn('frete_preco');
        });

        Schema::table('pedido_item', function ($table) {
            $table->dropColumn('preco_base');
            $table->dropColumn('coluna_a');
            $table->dropColumn('coluna_b');
            $table->dropColumn('coluna_c');
        });

    }
}
