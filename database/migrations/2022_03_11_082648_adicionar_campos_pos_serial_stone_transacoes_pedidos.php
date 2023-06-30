<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarCamposPosSerialStoneTransacoesPedidos extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('stone_transacoes_pedidos', function (Blueprint $table) {
            $table->string('pos_serial_number',50)->nullable();
            $table->string('siclos_transaction_id',100)->nullable();
            $table->dateTime('data_pre_transacao')->nullable();
            
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
            $table->dropColumn('pos_serial_number');
            $table->dropColumn('siclos_transaction_id');
            $table->dropColumn('data_pre_transacao');
        });
    }
}
