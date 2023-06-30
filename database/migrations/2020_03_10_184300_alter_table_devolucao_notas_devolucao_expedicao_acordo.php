<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucaoNotasDevolucaoExpedicaoAcordo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_notas', function ($table) {
            $table->boolean('devolucao_expedicao_acordo')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('devolucao_notas', function ($table) {
            $table->dropColumn('devolucao_expedicao_acordo');
        });
    }
}
