<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableCampoNuloNetrinApiRetornos extends Migration
{
    public function up()
    {
        Schema::table('netrin_api_retornos', function (Blueprint $table) {
            $table->integer('status_code')->nullable()->change();
      
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('netrin_api_retornos', function (Blueprint $table) {
            $table->integer('status_code')->change();

        });
    }
}
