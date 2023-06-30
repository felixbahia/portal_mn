<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CamposSerasaRevisaoCredito extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_creditos', function ($table) {
            $table->date('ultima_consulta_serasa')->nullable();
            $table->string('motivo_reavaliacao', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cliente_creditos', function ($table) {
            $table->dropColumn('ultima_consulta_serasa');
            $table->dropColumn('motivo_reavaliacao');
        });
    }
}
