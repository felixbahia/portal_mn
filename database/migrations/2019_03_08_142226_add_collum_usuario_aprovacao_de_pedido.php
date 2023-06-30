<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCollumUsuarioAprovacaoDePedido extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->integer('aprovacao_credito_user_id')->nullable();
            $table->integer('aprovacao_preco_user_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->dropColumn('aprovacao_credito_user_id');
            $table->dropColumn('aprovacao_preco_user_id');
        });
    }
}
