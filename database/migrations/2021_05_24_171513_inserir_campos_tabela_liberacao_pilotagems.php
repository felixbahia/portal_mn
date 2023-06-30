<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InserirCamposTabelaLiberacaoPilotagems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->string('pedido')->nullable();
            $table->uuid('pedido_id')->nullable();
            $table->string('titulo')->nullable();
            $table->uuid('titulo_id')->nullable();
            $table->date('data_emissao')->nullable();
            $table->date('data_vencimento')->nullable();
            $table->float('valor_pago')->nullable();
            $table->string('tipo_ajuste')->nullable();
            $table->string('motivo_recusa')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->dropColumn('pedido');
            $table->dropColumn('pedido_id');
            $table->dropColumn('titulo');
            $table->dropColumn('titulo_id');
            $table->dropColumn('data_emissao');
            $table->dropColumn('data_vencimento');
            $table->dropColumn('valor_pago');
            $table->dropColumn('tipo_ajuste')->nullable();
            $table->dropColumn('motivo_recusa')->nullable();
        });
    }
}
