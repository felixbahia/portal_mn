<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AltecaoCustoNota extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('valor_custo_notas', function (Blueprint $table) {
            $table->string('proforma')->nullable();
        });
        Schema::table('valor_custo_nota_produtos', function (Blueprint $table) {
            $table->string('proforma')->nullable();
            $table->float('quantidade')->nullable();
            $table->float('valor_dolar')->nullable();
        });
 
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('valor_custo_notas', function (Blueprint $table) {
            $table->dropColumn('proforma');
        });
        Schema::table('valor_custo_nota_produtos', function (Blueprint $table) {
            $table->dropColumn('proforma');
            $table->dropColumn('quantidade');
            $table->dropColumn('valor_dolar');
        });
    }
}
