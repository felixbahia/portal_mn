<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableMovimentacaoAddSegmento extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->integer('segmento_id')->nullable(); 
            $table->string('segmento')->nullable();       
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('movimentacao_recalculo', function (Blueprint $table) {
            $table->dropColumn('segmento_id');
            $table->dropColumn('segmento');
        });
    }
}
