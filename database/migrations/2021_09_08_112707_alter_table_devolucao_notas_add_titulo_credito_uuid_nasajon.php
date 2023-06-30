<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucaoNotasAddTituloCreditoUuidNasajon extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_notas', function ($table) {
            $table->string('titulo_credito_uuid_nasajon')->nullable();
            $table->string('titulo_credito_numero')->nullable();
            $table->float('titulo_credito_valor')->nullable();
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
            $table->dropColumn('titulo_credito_uuid_nasajon');
            $table->dropColumn('titulo_credito_numero');
            $table->dropColumn('titulo_credito_valor');
        });
    }
}