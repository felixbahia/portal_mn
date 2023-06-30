<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AprovacaoPedidoPre extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->boolean('pedido_pre')->default(false);
            $table->dateTime('data_aprovacao_pre_1auth')->nullable();
            $table->integer('user_aprovacao_pre_1auth')->nullable();
            $table->dateTime('data_aprovacao_pre_2auth')->nullable();
            $table->integer('user_aprovacao_pre_2auth')->nullable();

            $table->foreign('user_aprovacao_pre_1auth')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');

            $table->foreign('user_aprovacao_pre_2auth')
                ->references('id')
                ->on('users')
                ->onDelete('NO ACTION');
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
            $table->dropColumn('pedido_pre');
            $table->dropColumn('data_aprovacao_pre_1auth');
            $table->dropColumn('user_aprovacao_pre_1auth');
            $table->dropColumn('data_aprovacao_pre_2auth');
            $table->dropColumn('user_aprovacao_pre_2auth');
        });
    }
}
