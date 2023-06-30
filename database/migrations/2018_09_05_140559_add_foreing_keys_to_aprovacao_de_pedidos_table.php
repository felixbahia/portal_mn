<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddForeingKeysToAprovacaoDePedidosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_de_pedidos', function (Blueprint $table) {
            $table->foreign('pedido_id', 'fk_pedido_pedido_id')->references('id')->on('pedido')->onUpdate('NO ACTION')->onDelete('NO ACTION');
            $table->foreign('aprovador_id', 'fk_users_aorovador_id')->references('id')->on('users')->onUpdate('NO ACTION')->onDelete('NO ACTION');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aprovacao_de_pedidos', function (Blueprint $table) {
            $table->dropForeign('fk_pedido_pedido_id');
            $table->dropForeign('fk_users_aorovador_id');
        });
    }
}
