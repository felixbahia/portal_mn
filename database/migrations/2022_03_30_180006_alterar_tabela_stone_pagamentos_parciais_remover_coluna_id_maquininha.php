<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterarTabelaStonePagamentosParciaisRemoverColunaIdMaquininha extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->dropForeign(['stone_cadastro_maquininha_id']);
            $table->dropColumn('stone_cadastro_maquininha_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->integer('stone_cadastro_maquininha_id');
            $table->foreign('stone_cadastro_maquininha_id')
                ->references('id')
                ->on('stone_cadastro_maquininhas')
                ->onDelete('NO ACTION');
        });
    }
}
