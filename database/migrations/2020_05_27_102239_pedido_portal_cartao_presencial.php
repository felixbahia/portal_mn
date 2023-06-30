<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PedidoPortalCartaoPresencial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido', function ($table) {
            $table->boolean('cartao')->default(false);
            $table->boolean('presencial')->default(false);
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
            $table->dropColumn('cartao');
            $table->dropColumn('presencial');
        });
    }
}
