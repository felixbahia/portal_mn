<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AlterTableDevolucaoNotasTransportadorNotaCliente extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('devolucao_notas', function ($table) {
            $table->string('transportador')->nullable();
            $table->string('transportador_email')->nullable();
            $table->string('nota_cliente_numero')->nullable();
            $table->string('nota_cliente_arquivo')->nullable();
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
            $table->dropColumn('transportador');
            $table->dropColumn('transportador_email');
            $table->dropColumn('nota_cliente');
        });
    }
}
