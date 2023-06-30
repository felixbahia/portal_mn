<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableAjustesEstoque extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('ajustes_estoque', function ($table) {
            $table->string('pecas_codigo')->nullable()->change();
            $table->string('local_de_estoque_codigo')->nullable();
            $table->uuid('local_de_estoque_nasajon')->nullable();
            $table->uuid('movimento_ajuste_estoque_nasajon')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ajustes_estoque', function ($table) {
            $table->string('pecas_codigo')->change();
            $table->dropColumn('local_de_estoque_codigo');
            $table->dropColumn('local_de_estoque_nasajon');
            $table->dropColumn('movimento_ajuste_estoque_nasajon');
        });
    }
}
