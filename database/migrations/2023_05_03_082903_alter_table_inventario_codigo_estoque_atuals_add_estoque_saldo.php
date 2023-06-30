<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInventarioCodigoEstoqueAtualsAddEstoqueSaldo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventario_codigo_estoque_atuals', function ($table) {
            $table->float('estoque_saldo')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventario_codigo_estoque_atuals', function ($table) {
            $table->dropColumn('estoque_saldo');
        });
    }
}
