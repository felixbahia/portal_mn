<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UltimoCustoGerencialold extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produtos_custos', function (Blueprint $table) {
            $table->float('custo_medio_gerencial_antigo')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produtos_custos', function (Blueprint $table) {
            $table->dropColumn('custo_medio_gerencial_antigo');
        });
    }
}
