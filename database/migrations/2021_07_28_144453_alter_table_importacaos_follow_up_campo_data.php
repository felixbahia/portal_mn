<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaosFollowUpCampoData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_follow_up_historico_aprovacaos', function (Blueprint $table) {
            $table->date('data')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importacao_follow_up_historico_aprovacaos', function (Blueprint $table) {
            $table->date('data')->nullable(false)->change();
        });
    }
}
