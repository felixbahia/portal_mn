<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class HabilitarObservacaoEmRetornoCobranca extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('retorno_cobrancas', function ($table) {
            $table->boolean('observacao')->default(false)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('retorno_cobrancas', function ($table) {
            $table->dropColumn('observacao');
        });
    }
}
