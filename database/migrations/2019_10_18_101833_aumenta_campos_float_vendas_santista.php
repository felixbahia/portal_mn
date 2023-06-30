<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AumentaCamposFloatVendasSantista extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::table('vendas_santistas', function (Blueprint $table) {
            $table->float('quantidade', 10, 2)->change();
            $table->float('valor', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('vendas_santistas', function (Blueprint $table) {
            $table->float('quantidade', 8, 2)->change();
            $table->float('valor', 8, 2)->change();
        });
    }
}
