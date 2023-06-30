<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UltimaCompraDolarParaDate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido', function ($table) {
            $table->dropColumn('ultima_compra_dolar');
            $table->date('ultima_compra_dolar')->nullable();
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
            $table->dropColumn('ultima_compra_dolar');
            $table->float('ultima_compra_dolar')->nullable();
        });
    }
}
