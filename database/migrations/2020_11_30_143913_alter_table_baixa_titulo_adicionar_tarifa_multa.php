<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBaixaTituloAdicionarTarifaMulta extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('baixa_titulos', function (Blueprint $table) {
            $table->float('tarifa_bancaria_mn')->nullable();
            $table->float('multa')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('baixa_titulos', function (Blueprint $table) {
            $table->dropColumn('tarifa_bancaria_mn');
            $table->dropColumn('multa');
        });
    }
}
