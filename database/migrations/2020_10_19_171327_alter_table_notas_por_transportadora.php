<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableNotasPorTransportadora extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('notas_transportadora_itens', function (Blueprint $table) {
            $table->string('documento_cobranca_header')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('notas_transportadora_itens', function (Blueprint $table) {
            $table->dropColumn('documento_cobranca_header');
        });
    }
}
