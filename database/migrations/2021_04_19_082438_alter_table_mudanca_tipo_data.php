<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMudancaTipoData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('feriados', function (Blueprint $table) {
            $table->dropColumn('dia');
            $table->dropColumn('mes');
            $table->dropColumn('ano');
            $table->date('feriado');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('feriados', function (Blueprint $table) {
            $table->integer('dia');
            $table->integer('mes');
            $table->integer('ano')->nullable();
            $table->dropColumn('feriado');
        });
    }
}
