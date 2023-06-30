<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableInventarioCodigoLeiturasAddEnderecoContagem2E3 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('inventario_codigo_leituras', function ($table) {
            $table->string('endereco_lido_2')->nullable();
            $table->string('endereco_lido_3')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('inventario_codigo_leituras', function ($table) {
            $table->dropColumn('endereco_lido_2');
            $table->dropColumn('endereco_lido_3');
        });
    }
}
