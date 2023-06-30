<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCampoAprovacaoRenegociacao extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('renegociacao_titulos', function (Blueprint $table) {
            $table->boolean('confissao_divida')->default(true)->nullable();
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
            $table->dropColumn('confissao_divida');
        });
    }
}
