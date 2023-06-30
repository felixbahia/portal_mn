<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAddTransporteRodoviario extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_custos', function (Blueprint $table) {
            $table->float('transporte_rodoviario')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importacao_custos', function (Blueprint $table) {
            $table->dropColumn('transporte_rodoviario');
        });
    }
}
