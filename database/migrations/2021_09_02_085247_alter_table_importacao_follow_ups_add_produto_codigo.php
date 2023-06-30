<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableImportacaoFollowUpsAddProdutoCodigo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('importacao_follow_ups', function (Blueprint $table) {
            $table->string('produto_codigo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('importacao_follow_ups', function (Blueprint $table) {
            $table->dropColumn('produto_codigo');
        });
    }
}
