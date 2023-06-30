<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlteracaoFaturamentoOnline extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('faturamento_online', function ($table) {
            $table->string('nome_cliente', 200)->nullable(true);
            $table->boolean('nasajon')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('faturamento_online', function ($table) {
            $table->dropColumn('nome_cliente');
            $table->dropColumn('nasajon');
        });
    }
}
