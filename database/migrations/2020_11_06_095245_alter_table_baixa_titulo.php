<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableBaixaTituloD extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('baixa_titulos', function (Blueprint $table) {
            $table->boolean('quitar')->nullable();
            $table->string('observacao')->nullable();
            $table->text('retorno_nasajon')->nullable();
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
            $table->dropColumn('quitar');
            $table->dropColumn('observacao');
            $table->dropColumn('retorno_nasajon');
        });
    }
}
