<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DeletarCamposTabelaLiberacaoPilotagems extends Migration
{
     /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->dropColumn('tipo_ajuste');
            $table->dropColumn('motivo_recusa');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->string('tipo_ajuste')->nullable();
            $table->string('motivo_recusa')->nullable();
        });
       
    }
}
