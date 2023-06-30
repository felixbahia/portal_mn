<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCampoStoneTransacaoesParcial extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->boolean('pagamento_parcial')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->dropColumn('pagamento_parcial');
        });
    }
}
