<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FaturamentoOnlineTransportador extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('faturamento_online', function (Blueprint $table) {
            $table->float('valor_frete_cobrado')->default('0');
            $table->uuid('transportador_uuid')->nullable();
            $table->string('transportador_codigo')->nullable();
            $table->string('tipo_frete', 3)->nullable();
            $table->uuid('nota_uuid')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('faturamento_online', function (Blueprint $table) {
            $table->dropColumn('valor_frete_cobrado');
            $table->dropColumn('transportador_uuid');
            $table->dropColumn('transportador_codigo');
            $table->dropColumn('tipo_frete');
            $table->dropColumn('nota_uuid');
        });
    }
}
