<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableEstoqueAcrescentarCampos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('produtos_estoques', function ($table) {
            $table->float('saldo_fiscal')->nullable();
            $table->float('saldo_em_terceiros')->nullable();
            $table->float('saldo_de_terceiros')->nullable();
            $table->float('saldo_armazem')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('produtos_estoques', function ($table) {
            $table->dropColumn('saldo_fiscal');
            $table->dropColumn('saldo_em_terceiros');
            $table->dropColumn('saldo_de_terceiros');
            $table->dropColumn('saldo_armazem');
        });
    }
}
