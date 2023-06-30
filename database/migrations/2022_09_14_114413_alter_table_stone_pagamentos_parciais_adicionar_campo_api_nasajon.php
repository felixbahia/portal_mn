<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableStonePagamentosParciaisAdicionarCampoApiNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_pagamentos_parciais', function (Blueprint $table) {
            $table->boolean('api_nasajon')->nullable();  
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
            $table->dropColumn('api_nasajon');
        });
    }
}
