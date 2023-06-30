<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasImportadasEntradasIncluirCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->float('frete_peso')->nullable();
            $table->float('valor_descarga')->nullable();
            $table->float('valor_trt')->nullable();
            $table->float('valor_Taxa_emi_ctrc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_importadas_entradas', function (Blueprint $table) {
            $table->dropColumn('frete_peso');
            $table->dropColumn('valor_descarga');
            $table->dropColumn('valor_trt');
            $table->dropColumn('valor_Taxa_emi_ctrc');
        });
    }
}
