<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableTitulosAbertosNasajonViradaAddCreatedAtPercentualComissao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('titulos_abertos_nasajon_viradas', function ($table) {
            $table->float('percentual_comissao')->nullable();
            $table->datetime('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('titulos_abertos_nasajon_viradas', function ($table) {
            $table->dropColumn('percentual_comissao');
            $table->dropColumn('created_at');
        });
    }
}
