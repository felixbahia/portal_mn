<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterFaturamentoOnline2 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('faturamento_online', function ($table) {
            $table->string('numero_documento', 50)->nullable(true)->change();
            $table->string('codigo_cadastro', 50)->nullable(true)->change();
            $table->string('tipo_operacao', 50)->nullable(true)->change();
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
            $table->string('numero_documento', 50)->nullable(true)->change();
            $table->string('codigo_cadastro', 50)->nullable(true)->change();
            $table->string('tipo_operacao', 50)->nullable(true)->change();
        });
    }
}
