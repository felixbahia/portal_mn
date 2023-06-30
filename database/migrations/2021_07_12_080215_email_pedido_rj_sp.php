<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class EmailPedidoRjSp extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pedido_rj_sp', function (Blueprint $table) {
            $table->boolean('email_transportadora')->default(false);
            $table->boolean('email_pedido')->default(false);
            $table->dropColumn('pedido_venda_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pedido_rj_sp', function (Blueprint $table) {
            $table->dropColumn('email_transportadora');
            $table->dropColumn('email_pedido');
            $table->integer('pedido_venda_id')->nullable();
        });
    }
}
