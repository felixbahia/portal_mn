<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucaoNotasNotaRemessaUuid extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_notas', function ($table) {
            $table->dropColumn('nota_remessa');
        });

        Schema::table('devolucao_notas', function ($table) {
            $table->uuid('nota_remessa')->nullable();
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
            $table->dropColumn('nota_remessa');
        });

        Schema::table('devolucao_notas', function ($table) {
            $table->string('nota_remessa')->nullable();
        });
    }
}
