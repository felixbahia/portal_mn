<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterAddColumnInscricaoEstadualIndicador extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cliente_novos', function (Blueprint $table) {
            $table->integer('inscricao_estadual_indicador')->after('inscricao_estadual')->default('0');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cliente_novos', function (Blueprint $table) {
            //
            $table->dropColumn('inscricao_estadual_indicador');
        });
    }
}
