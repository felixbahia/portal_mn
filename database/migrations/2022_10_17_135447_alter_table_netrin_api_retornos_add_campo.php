<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNetrinApiRetornosAddCampo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('netrin_api_retornos', function (Blueprint $table) {
            $table->boolean('atualizado_nasajon')->nullable();
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
            $table->dropColumn('atualizado_nasajon');

        });
    }
}
