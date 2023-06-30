<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaClicksignDocumentos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->integer('clicksign_documentos_id')->nullable();

            $table->foreign('clicksign_documentos_id')
                ->references('id')
                ->on('clicksign_documentos')
                ->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->dropColumn('clicksign_documentos_id');
        });
    }
}
