<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AprovacaoPedidoTravaDeProrrogado extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('aprovacao_de_pedidos', function ($table) {
            $table->boolean('prorrogacao')->default(false)->nullable();
            $table->integer('prorrogacao_user_id')->nullable();

            $table->foreign('prorrogacao_user_id')
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
            $table->dropColumn('prorrogacao');
            $table->dropColumn('prorrogacao_user_id');
        });
    }
}
