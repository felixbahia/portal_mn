<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenomearCamposTabelaLiberacaoPilotagems extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('liberacao_pilotagems', function (Blueprint $table) {
            $table->renameColumn('pedido', 'pedido_numero');
            $table->renameColumn('pedido_id', 'pedido_nasajon_id');
            $table->renameColumn('titulo', 'titulo_numero');
            $table->renameColumn('data_emissao', 'titulo_data_emissaoo');
            $table->renameColumn('data_vencimento', 'titulo_data_vencimento');
            $table->renameColumn('valor_pago', 'titulo_valor');
            $table->renameColumn('nota', 'nota_numero');
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
            $table->renameColumn('pedido_numero', 'pedido');
            $table->renameColumn('titulo_numero','titulo');
            $table->renameColumn('pedido_nasajon_id', 'pedido_id');
            $table->renameColumn('titulo_data_emissao', 'data_emissao');
            $table->renameColumn('titulo_data_vencimento','data_vencimento');
            $table->renameColumn('titulo_valor','valor_pago');
            $table->renameColumn('nota_numero', 'nota');
        });
    }
}
