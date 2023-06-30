<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AdicionarColunaPagoStoneTransacoes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $pago = Schema::hasColumn('stone_transacoes_pedidos', 'pago');
        $pagamento_restante = Schema::hasColumn('stone_transacoes_pedidos', 'pagamento_restante');

        if($pago == false){
            Schema::table('stone_transacoes_pedidos', function (Blueprint $table){
                $table->boolean('pago')->nullable();
            });
        }

        if($pagamento_restante == false){
            Schema::table('stone_transacoes_pedidos', function (Blueprint $table){
                $table->boolean('pagamento_restante')->nullable();
            });
        }

        Schema::table('stone_transacoes_pedidos', function (Blueprint $table){
            $table->boolean('credito')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $pago = Schema::hasColumn('stone_transacoes_pedidos', 'pago');
        $pagamento_restante = Schema::hasColumn('stone_transacoes_pedidos', 'pagamento_restante');

        if($pago == true){
            Schema::table('stone_transacoes_pedidos', function (Blueprint $table){
                $table->dropColumn(['pago']);
            });
        }

        if($pagamento_restante == true){
            Schema::table('stone_transacoes_pedidos', function (Blueprint $table){
                $table->dropColumn(['pagamento_restante']);
            });
        }

        Schema::table('stone_transacoes_pedidos', function (Blueprint $table){
            $table->dropColumn('credito')->nullable();
        });
    }
}
